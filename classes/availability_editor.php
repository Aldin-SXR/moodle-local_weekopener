<?php
/**
 * Sets and removes a section's opening date inside its existing availability
 * conditions, leaving every other condition as it was.
 *
 * @package local_weekopener
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class availability_editor {

    /**
     * The opening date is a top-level "from" date condition of an AND tree. Any
     * such condition already there is replaced, whoever set it, since a section
     * cannot sensibly have two; "until" dates and everything else stay.
     *
     * A root that is not an AND cannot take another condition without changing
     * what it means, so it becomes a subtree next to the date.
     */
    public static function with_opening(?string $availability, int $time, bool $hidden): string {
        $date = (object)['type' => 'date', 'd' => '>=', 't' => $time];
        $tree = self::decode($availability);

        if ($tree === null) {
            return self::encode((object)['op' => '&', 'c' => [$date], 'showc' => [!$hidden]]);
        }

        if ($tree->op === '&') {
            [$children, $showc] = self::without_opening_children($tree);
            array_unshift($children, $date);
            array_unshift($showc, !$hidden);
            $tree->c = $children;
            $tree->showc = $showc;
            return self::encode($tree);
        }

        $show = isset($tree->showc) ? !in_array(false, $tree->showc, true) : (bool)($tree->show ?? true);
        unset($tree->show, $tree->showc);
        return self::encode((object)['op' => '&', 'c' => [$date, $tree], 'showc' => [!$hidden, $show]]);
    }

    /** @return ?string The conditions without an opening date, or null when nothing is left. */
    public static function without_opening(?string $availability): ?string {
        $tree = self::decode($availability);
        if ($tree === null) {
            return null;
        }
        if ($tree->op !== '&') {
            return $availability;
        }

        [$tree->c, $tree->showc] = self::without_opening_children($tree);
        return $tree->c ? self::encode($tree) : null;
    }

    /** @return ?array{time: int, hidden: bool} The section's opening date, if it has one. */
    public static function opening(?string $availability): ?array {
        $tree = self::decode($availability);
        if ($tree === null || $tree->op !== '&') {
            return null;
        }

        $opening = null;
        foreach ($tree->c as $index => $child) {
            if (self::is_opening($child) && ($opening === null || $child->t > $opening['time'])) {
                $opening = ['time' => (int)$child->t, 'hidden' => empty($tree->showc[$index])];
            }
        }
        return $opening;
    }

    private static function is_opening(\stdClass $child): bool {
        return ($child->type ?? null) === 'date' && ($child->d ?? null) === '>=';
    }

    /** @return array{0: array, 1: bool[]} */
    private static function without_opening_children(\stdClass $tree): array {
        $children = [];
        $showc = [];
        foreach ($tree->c as $index => $child) {
            if (!self::is_opening($child)) {
                $children[] = $child;
                $showc[] = $tree->showc[$index] ?? true;
            }
        }
        return [$children, $showc];
    }

    private static function decode(?string $availability): ?\stdClass {
        if ($availability === null || trim($availability) === '') {
            return null;
        }
        $tree = json_decode($availability);
        if (!$tree instanceof \stdClass || !isset($tree->op) || !is_array($tree->c ?? null)) {
            throw new \coding_exception('Invalid availability structure: ' . $availability);
        }
        // An empty tree is what the editor saves once every condition is deleted.
        return $tree->c ? $tree : null;
    }

    private static function encode(\stdClass $tree): string {
        return json_encode($tree);
    }
}
