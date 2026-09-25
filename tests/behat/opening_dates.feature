@local @local_weekopener
Feature: Setting the dates a course's sections open on
  In order to release a course a section at a time
  As a teacher
  I need to give every section an opening date in one go

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | numsections |
      | Opener   | OPEN      | topics | 5           |
    And the following "users" exist:
      | username | firstname | lastname |
      | prof     | Pat       | Teacher  |
      | stu      | Sam       | Student  |
    And the following "course enrolments" exist:
      | user | course | role           |
      | prof | OPEN   | editingteacher |
      | stu  | OPEN   | student        |
    And the following "activities" exist:
      | activity | course | name      | section |
      | page     | OPEN   | Reading 1 | 1       |
      | page     | OPEN   | Reading 3 | 3       |
      | page     | OPEN   | Reading 4 | 4       |

  @javascript
  Scenario: Sections open a week apart, a skipped section keeps its place out of the count
    Given I log in as "prof"
    And I am on "Opener" course homepage
    And I navigate to "Opening dates" in current page administration
    When I set the following fields to these values:
      | Starting section    | 2. New section |
      | id_starttime_day    | 5              |
      | id_starttime_month  | October        |
      | id_starttime_year   | 2030           |
      | id_starttime_hour   | 00             |
      | id_starttime_minute | 00             |
      | Days between sections | 7            |
      | Skip these sections | 3. New section |
    And I press "Apply opening dates"
    Then I should see "Opening dates applied. 3 section(s) changed."
    And the following should exist in the "local-weekopener-current" table:
      | Section        | Opens                                  |
      | 1. New section | Open now                               |
      | 2. New section | Saturday, 5 October 2030, 12:00 AM     |
      | 3. New section | Skipped                                |
      | 4. New section | Saturday, 12 October 2030, 12:00 AM    |
      | 5. New section | Saturday, 19 October 2030, 12:00 AM    |
    And the field "Days between sections" matches value "7"
    When I log out
    And I log in as "stu"
    And I am on "Opener" course homepage
    Then I should see "Reading 1"
    And I should see "Reading 3"
    And I should not see "Reading 4"
    And I should see "Available from"

  @javascript
  Scenario: Hidden sections do not appear to students at all
    Given I log in as "prof"
    And I am on "Opener" course homepage
    And I navigate to "Opening dates" in current page administration
    When I set the following fields to these values:
      | Starting section       | 2. New section     |
      | id_starttime_year      | 2030               |
      | Until a section opens  | Hide it completely |
    And I press "Apply opening dates"
    And I log out
    And I log in as "stu"
    And I am on "Opener" course homepage
    Then I should see "Reading 1"
    And I should not see "Reading 3"
    And I should not see "Available from"

  @javascript
  Scenario: Removing the opening dates opens every section again
    Given I log in as "prof"
    And I am on "Opener" course homepage
    And I navigate to "Opening dates" in current page administration
    And I set the following fields to these values:
      | Starting section  | 1. New section |
      | id_starttime_year | 2030           |
    And I press "Apply opening dates"
    When I click on "Remove all opening dates" "link"
    And I click on "Yes" "button" in the "Confirmation" "dialogue"
    Then I should see "Opening dates removed. 5 section(s) changed."
    And I should not see "October 2030"

  @javascript
  Scenario: A section hidden with the eye icon is marked in the table
    Given I log in as "prof"
    And I am on "Opener" course homepage with editing mode on
    And I hide section "3"
    And I navigate to "Opening dates" in current page administration
    When I set the following fields to these values:
      | Starting section  | 2. New section |
      | id_starttime_year | 2030           |
    And I press "Apply opening dates"
    Then the following should exist in the "local-weekopener-current" table:
      | Section        | Opens  |
      | 3. New section | Hidden |
    And the following should not exist in the "local-weekopener-current" table:
      | Section        | Opens  |
      | 4. New section | Hidden |

  Scenario: Students cannot reach the page
    Given I log in as "stu"
    When I am on the "Opener" "course" page
    Then "Opening dates" "link" should not exist in current page administration
