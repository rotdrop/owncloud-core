Feature: webdav-related
	Background:
		Given using api version "1"

	Scenario: Moving a file
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" moves file "/welcome.txt" to "/FOLDER/welcome.txt"
		Then the HTTP status code should be "201"
		And Downloaded content when downloading file "/FOLDER/welcome.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Moving and overwriting a file old way
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" moves file "/welcome.txt" to "/textfile0.txt"
		Then the HTTP status code should be "204"
		And Downloaded content when downloading file "/textfile0.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Moving a file to a folder with no permissions
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And As an "user0"
		And User "user0" moves file "/textfile0.txt" to "/testshare/textfile0.txt"
		And the HTTP status code should be "403"
		When Downloading file "/testshare/textfile0.txt"
 		Then the HTTP status code should be "404"

	Scenario: Moving a file to overwrite a file in a folder with no permissions
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And User "user1" copies file "/welcome.txt" to "/testshare/overwritethis.txt"
		And As an "user0"
		When User "user0" moves file "/textfile0.txt" to "/testshare/overwritethis.txt"
		Then the HTTP status code should be "403"
		And Downloaded content when downloading file "/testshare/overwritethis.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Copying a file
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" copies file "/welcome.txt" to "/FOLDER/welcome.txt"
		Then the HTTP status code should be "201"
		And Downloaded content when downloading file "/FOLDER/welcome.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Copying and overwriting a file
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" copies file "/welcome.txt" to "/textfile1.txt"
		Then the HTTP status code should be "204"
		And Downloaded content when downloading file "/textfile1.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Copying a file to a folder with no permissions
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And As an "user0"
		When User "user0" copies file "/textfile0.txt" to "/testshare/textfile0.txt"
		Then the HTTP status code should be "403"
		And Downloading file "/testshare/textfile0.txt"
		And the HTTP status code should be "404"

	Scenario: Copying a file to overwrite a file into a folder with no permissions
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And User "user1" copies file "/welcome.txt" to "/testshare/overwritethis.txt"
		And As an "user0"
		When User "user0" copies file "/textfile0.txt" to "/testshare/overwritethis.txt"
		Then the HTTP status code should be "403"
		And Downloaded content when downloading file "/testshare/overwritethis.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: download a file with range
		Given using dav path "remote.php/webdav"
		And As an "admin"
		When Downloading file "/welcome.txt" with range "bytes=51-77"
		Then Downloaded content should be "example file for developers"

	Scenario: Upload forbidden if quota is 0
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user0" has a quota of "0"
		When User "user0" uploads file "data/textfile.txt" to "/asdf.txt"
		Then the HTTP status code should be "507"

	Scenario: Retrieving folder quota when quota is set
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		When user "user0" has a quota of "10 MB"
		Then as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "10485429"

	Scenario: Retrieving folder quota of shared folder with quota when no quota is set for recipient
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user0" has unlimited quota
		And user "user1" has a quota of "10 MB"
		And As an "user1"
		And user "user1" created a folder "/testquota"
		And as "user1" creating a share with
		  | path | testquota |
		  | shareType | 0 |
		  | permissions | 31 |
		  | shareWith | user0 |
		Then as "user0" gets properties of folder "/testquota" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "10485429"

	Scenario: Uploading a file as recipient using webdav having quota
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user0" has a quota of "10 MB"
		And user "user1" has a quota of "10 MB"
		And As an "user1"
		And user "user1" created a folder "/testquota"
		And as "user1" creating a share with
		  | path | testquota |
		  | shareType | 0 |
		  | permissions | 31 |
		  | shareWith | user0 |
		And As an "user0"
		When User "user0" uploads file "data/textfile.txt" to "/testquota/asdf.txt"
		Then the HTTP status code should be "201"

	Scenario: download a public shared file with range
		Given user "user0" exists
		And As an "user0"
		When creating a share with
			| path | welcome.txt |
			| shareType | 3 |
		And Downloading last public shared file with range "bytes=51-77"
		Then Downloaded content should be "example file for developers"

	Scenario: download a public shared file inside a folder with range
		Given user "user0" exists
		And As an "user0"
		When creating a share with
			| path | PARENT |
			| shareType | 3 |
		And Downloading last public shared file inside a folder "/parent.txt" with range "bytes=1-7"
		Then Downloaded content should be "wnCloud"

	Scenario: Doing a GET with a web login should work without CSRF token on the old backend
		Given Logging in using web as "admin"
		When Sending a "GET" to "/remote.php/webdav/welcome.txt" without requesttoken
		Then Downloaded content should start with "Welcome to your ownCloud account!"
		And the HTTP status code should be "200"

	Scenario: Upload chunked file asc
		Given user "user0" exists
		And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file desc
		Given user "user0" exists
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file random
		Given user "user0" exists
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"
