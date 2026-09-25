<?php
/**
 * Behat steps for the opening dates page.
 *
 * @package local_weekopener
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

class behat_local_weekopener extends behat_base {

    /**
     * @Then /^the suggestions for "([^"]*)" are not scrolled to the top$/
     */
    public function the_suggestions_are_not_scrolled_to_the_top(string $fieldid): void {
        $top = $this->evaluate_script(
            "return document.querySelector('#fitem_{$fieldid} ul.form-autocomplete-suggestions').scrollTop"
        );
        if ((int)$top === 0) {
            throw new ExpectationException('The suggestions list jumped back to the top', $this->getSession());
        }
    }
}
