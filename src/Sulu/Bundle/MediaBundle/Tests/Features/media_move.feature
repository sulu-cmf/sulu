Feature: Media move
    In order to organize my medias
    As a user
    I need to be able to move medias

    Background:
        Given I am logged in as an administrator

    Scenario: Move media
        Given the media collection "Foobar" exists
        And the file "image1.png" has been uploaded to the "Dornbirn" collection
        And the file "image2.png" has been uploaded to the "Dornbirn" collection
        And the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        And the file "image4.jpg" has been uploaded to the "Dornbirn" collection
        When I am editing the media collection "Foobar"
        Then I expect to see "0" ".item" elements
        When I am editing the media collection "Dornbirn"
        Then I expect to see "4" ".item" elements
        When I click on the element ".item:nth-child(1)"
        And I click toolbar item "move"
        And I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        And I double click the column navigation item "Foobar"
        Then I expect a success notification to appear
        And I expect to see "3" ".item" elements
        When I am editing the media collection "Foobar"
        Then I expect to see "1" ".item" elements

    Scenario: Move multiple media
        Given the media collection "Foobar" exists
        And the file "image1.png" has been uploaded to the "Dornbirn" collection
        And the file "image2.png" has been uploaded to the "Dornbirn" collection
        And the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        And the file "image4.jpg" has been uploaded to the "Dornbirn" collection
        When I am editing the media collection "Foobar"
        Then I expect to see "0" ".item" elements
        And I am editing the media collection "Dornbirn"
        And I expect to see "4" ".item" elements
        When I click on the element ".item:nth-child(1)"
        And I click on the element ".item:nth-child(3)"
        And I click toolbar item "move"
        Then I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        When I double click the column navigation item "Foobar"
        Then I expect a success notification to appear
        And I expect to see "2" ".item" elements
        When I am editing the media collection "Foobar"
        Then I expect to see "2" ".item" elements
