<?php
/**
 * Setting a section's opening date inside its existing restrictions.
 *
 * @package local_weekopener
 * @covers  \local_weekopener\availability_editor
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class availability_editor_test extends \advanced_testcase {

    private const T = 1791158400;

    /** What the restriction editor saves for "must be in group 5". */
    private const GROUP = '{"op":"&","c":[{"type":"group","id":5}],"showc":[true]}';

    /** Whatever the editor writes must be a tree Moodle accepts. */
    private function assert_valid(?string $json): void {
        if ($json !== null) {
            new \core_availability\tree(json_decode($json));
        }
        $this->assertTrue(true);
    }

    public function test_a_section_without_restrictions_gets_just_the_date(): void {
        $json = availability_editor::with_opening(null, self::T, false);

        $this->assertSame('{"op":"&","c":[{"type":"date","d":">=","t":' . self::T . '}],"showc":[true]}', $json);
        $this->assert_valid($json);
    }

    public function test_hidden_turns_off_show_for_the_date_only(): void {
        $json = availability_editor::with_opening(self::GROUP, self::T, true);

        $tree = json_decode($json);
        $this->assertSame([false, true], $tree->showc);
        $this->assertSame('group', $tree->c[1]->type);
        $this->assert_valid($json);
    }

    public function test_other_restrictions_are_kept(): void {
        $json = availability_editor::with_opening(self::GROUP, self::T, false);

        $tree = json_decode($json);
        $this->assertSame('&', $tree->op);
        $this->assertCount(2, $tree->c);
        $this->assertEquals((object)['type' => 'group', 'id' => 5], $tree->c[1]);
    }

    public function test_an_existing_opening_date_is_replaced_and_an_until_date_kept(): void {
        $existing = '{"op":"&","c":[{"type":"date","d":">=","t":100},{"type":"date","d":"<","t":200}],'
            . '"showc":[false,true]}';

        $json = availability_editor::with_opening($existing, self::T, false);

        $tree = json_decode($json);
        $this->assertCount(2, $tree->c);
        $this->assertSame(self::T, $tree->c[0]->t);
        $this->assertSame('<', $tree->c[1]->d);
        $this->assertSame([true, true], $tree->showc);
    }

    public function test_applying_twice_changes_nothing_the_second_time(): void {
        $once = availability_editor::with_opening(self::GROUP, self::T, true);

        $this->assertSame($once, availability_editor::with_opening($once, self::T, true));
    }

    public function test_an_or_tree_is_kept_whole_beside_the_date(): void {
        $or = '{"op":"|","c":[{"type":"group","id":5},{"type":"group","id":6}],"show":false}';

        $json = availability_editor::with_opening($or, self::T, false);

        $tree = json_decode($json);
        $this->assertSame('&', $tree->op);
        $this->assertSame([true, false], $tree->showc);
        $this->assertSame('|', $tree->c[1]->op);
        $this->assertObjectNotHasProperty('show', $tree->c[1]);
        $this->assert_valid($json);

        $this->assertSame($json, availability_editor::with_opening($json, self::T, false));
    }

    public function test_an_empty_tree_counts_as_no_restrictions(): void {
        $json = availability_editor::with_opening('{"op":"&","c":[],"showc":[]}', self::T, false);

        $this->assertCount(1, json_decode($json)->c);
    }

    public function test_removing_the_date_keeps_the_rest(): void {
        $json = availability_editor::with_opening(self::GROUP, self::T, false);

        $this->assertSame(self::GROUP, availability_editor::without_opening($json));
    }

    public function test_removing_the_only_condition_leaves_no_restrictions(): void {
        $json = availability_editor::with_opening(null, self::T, false);

        $this->assertNull(availability_editor::without_opening($json));
        $this->assertNull(availability_editor::without_opening(null));
    }

    public function test_an_or_tree_has_no_opening_date_to_remove(): void {
        $or = '{"op":"|","c":[{"type":"date","d":">=","t":100}],"show":true}';

        $this->assertSame($or, availability_editor::without_opening($or));
        $this->assertNull(availability_editor::opening($or));
    }

    public function test_the_opening_date_reads_back(): void {
        $this->assertSame(
            ['time' => self::T, 'hidden' => true],
            availability_editor::opening(availability_editor::with_opening(self::GROUP, self::T, true))
        );
        $this->assertNull(availability_editor::opening(self::GROUP));
        $this->assertNull(availability_editor::opening(null));
    }
}
