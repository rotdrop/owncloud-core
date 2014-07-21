<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * Copyright (c) 2013 Vincent Petry <pvince81@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ . '/../../../lib/base.php';

require __DIR__ . '/../lib/config.php';

class Test_Mount_Config_Dummy_Storage {
	public function test() {
		return true;
	}
}

/**
 * Class Test_Mount_Config
 */
class Test_Mount_Config extends \PHPUnit_Framework_TestCase {

	private $dataDir;
	private $userHome;
	private $oldAllowedBackends;
	private $allBackends;

	const TEST_USER1 = 'user1';
	const TEST_USER2 = 'user2';
	const TEST_GROUP1 = 'group1';
	const TEST_GROUP2 = 'group2';

	public function setUp() {
		\OC_User::createUser(self::TEST_USER1, self::TEST_USER1);
		\OC_User::createUser(self::TEST_USER2, self::TEST_USER2);

		\OC_Group::createGroup(self::TEST_GROUP1);
		\OC_Group::addToGroup(self::TEST_USER1, self::TEST_GROUP1);
		\OC_Group::createGroup(self::TEST_GROUP2);
		\OC_Group::addToGroup(self::TEST_USER2, self::TEST_GROUP2);

		\OC_User::setUserId(self::TEST_USER1);
		$this->userHome = \OC_User::getHome(self::TEST_USER1);
		mkdir($this->userHome);

		$this->dataDir = \OC_Config::getValue(
			'datadirectory',
			\OC::$SERVERROOT . '/data/'
		);
		$this->oldAllowedBackends = OCP\Config::getAppValue(
			'files_external',
			'user_mounting_backends',
			''
		);
		$this->allBackends = OC_Mount_Config::getBackends();
		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			implode(',', array_keys($this->allBackends))
		);

		OC_Mount_Config::$skipTest = true;
	}

	public function tearDown() {
		OC_Mount_Config::$skipTest = false;

		\OC_User::deleteUser(self::TEST_USER2);
		\OC_User::deleteUser(self::TEST_USER1);
		\OC_Group::deleteGroup(self::TEST_GROUP1);
		\OC_Group::deleteGroup(self::TEST_GROUP2);

		@unlink($this->dataDir . '/mount.json');

		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			$this->oldAllowedBackends
		);
	}

	/**
	 * Reads the global config, for checking
	 */
	private function readGlobalConfig() {
		$configFile = $this->dataDir . '/mount.json';
		return json_decode(file_get_contents($configFile), true);
	}

	/**
	 * Reads the user config, for checking
	 */
	private function readUserConfig() {
		$configFile = $this->userHome . '/mount.json';
		return json_decode(file_get_contents($configFile), true);
	}

	/**
	 * Write the user config, to simulate existing files
	 */
	private function writeUserConfig($config) {
		$configFile = $this->userHome . '/mount.json';
		file_put_contents($configFile, json_encode($config));
	}

	/**
	 * Test mount point validation
	 */
	public function testAddMountPointValidation() {
		$storageClass = 'Test_Mount_Config_Dummy_Storage';
		$mountType = 'user';
		$applicable = 'all';
		$isPersonal = false;
		$this->assertFalse(OC_Mount_Config::addMountPoint('', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertFalse(OC_Mount_Config::addMountPoint('/', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertFalse(OC_Mount_Config::addMountPoint('Shared', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertFalse(OC_Mount_Config::addMountPoint('/Shared', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}

	/**
	 * Test adding a global mount point
	 */
	public function testAddGlobalMountPoint() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'all';
		$isPersonal = false;

		$this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', array(), $mountType, $applicable, $isPersonal));

		$config = $this->readGlobalConfig();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config[$mountType]));
		$this->assertTrue(isset($config[$mountType][$applicable]));
		$this->assertTrue(isset($config[$mountType][$applicable]['/$user/files/ext']));
		$this->assertEquals(
			'\OC\Files\Storage\SFTP',
			$config[$mountType][$applicable]['/$user/files/ext']['class']
		);
	}

	/**
	 * Test adding a personal mount point
	 */
	public function testAddMountPointSingleUser() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = true;

		$this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', array(), $mountType, $applicable, $isPersonal));

		$config = $this->readUserConfig();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config[$mountType]));
		$this->assertTrue(isset($config[$mountType][$applicable]));
		$this->assertTrue(isset($config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']));
		$this->assertEquals(
			'\OC\Files\Storage\SFTP',
			$config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']['class']
		);
	}

	/**
	 * Test adding a mount point with an non-existant backend
	 */
	public function testAddMountPointUnexistClass() {
		$storageClass = 'Unexist_Storage';
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = false;
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}

	/**
	 * Test reading and writing global config
	 */
	public function testReadWriteGlobalConfig() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'all';
		$isPersonal = false;
		$mountConfig = array(
			'host' => 'smbhost',
			'user' => 'smbuser',
			'password' => 'smbpassword',
			'share' => 'smbshare',
			'root' => 'smbroot'
		);

		// write config
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getSystemMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config['ext']));
		$this->assertEquals('\OC\Files\Storage\SMB', $config['ext']['class']);
		$savedMountConfig = $config['ext']['configuration'];
		$this->assertEquals($mountConfig, $savedMountConfig);
		// key order needs to be preserved for the UI...
		$this->assertEquals(array_keys($mountConfig), array_keys($savedMountConfig));
	}

	/**
	 * Test reading and writing config
	 */
	public function testReadWritePersonalConfig() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = true;
		$mountConfig = array(
			'host' => 'smbhost',
			'user' => 'smbuser',
			'password' => 'smbpassword',
			'share' => 'smbshare',
			'root' => 'smbroot'
		);

		// write config
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getPersonalMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config['ext']));
		$this->assertEquals('\OC\Files\Storage\SMB', $config['ext']['class']);
		$savedMountConfig = $config['ext']['configuration'];
		$this->assertEquals($mountConfig, $savedMountConfig);
		// key order needs to be preserved for the UI...
		$this->assertEquals(array_keys($mountConfig), array_keys($savedMountConfig));
	}

	/**
	 * Test password obfuscation
	 */
	public function testPasswordObfuscation() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = true;
		$mountConfig = array(
			'host' => 'smbhost',
			'user' => 'smbuser',
			'password' => 'smbpassword',
			'share' => 'smbshare',
			'root' => 'smbroot'
		);

		// write config
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// note: password re-reading is covered by testReadWritePersonalConfig

		// check that password inside the file is NOT in plain text
		$config = $this->readUserConfig();
		$savedConfig = $config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']['options'];

		// no more clear text password in file (kept because of key order)
		$this->assertEquals('', $savedConfig['password']);

		// encrypted password is present
		$this->assertNotEquals($mountConfig['password'], $savedConfig['password_encrypted']);
	}

	/**
	 * Test read legacy passwords
	 */
	public function testReadLegacyPassword() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = true;
		$mountConfig = array(
			'host' => 'smbhost',
			'user' => 'smbuser',
			'password' => 'smbpassword',
			'share' => 'smbshare',
			'root' => 'smbroot'
		);

		// write config
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		$config = $this->readUserConfig();
		// simulate non-encrypted password situation
		$config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']['options']['password'] = 'smbpasswd';

		$this->writeUserConfig($config);

		// re-read config, password was read correctly
		$config = OC_Mount_Config::getPersonalMountPoints();
		$savedMountConfig = $config['ext']['configuration'];
		$this->assertEquals($mountConfig, $savedMountConfig);
	}

	public function mountDataProvider() {
		return array(
			// Tests for visible mount points
			// system mount point for all users
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_USER,
				'all',
				self::TEST_USER1,
				true,
			),
			// system mount point for a specific user
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				self::TEST_USER1,
				true,
			),
			// system mount point for a specific group
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP1,
				self::TEST_USER1,
				true,
			),
			// user mount point
			array(
				true,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				self::TEST_USER1,
				true,
			),

			// Tests for non-visible mount points
			// system mount point for another user
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER2,
				self::TEST_USER1,
				false,
			),
			// system mount point for a specific group
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP2,
				self::TEST_USER1,
				false,
			),
			// user mount point
			array(
				true,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				self::TEST_USER2,
				false,
			),
		);
	}

	/**
	 * Test mount points used at mount time, making sure
	 * the configuration is prepared properly.
	 *
	 * @dataProvider mountDataProvider
	 * @param bool $isPersonal true for personal mount point, false for system mount point
	 * @param string $mountType mount type
	 * @param string $applicable target user/group or "all"
	 * @param string $testUser user for which to retrieve the mount points
	 * @param bool $expectVisible whether to expect the mount point to be visible for $testUser
	 */
	public function testMount($isPersonal, $mountType, $applicable, $testUser, $expectVisible) {
		$mountConfig = array(
			'host' => 'someost',
			'user' => 'someuser',
			'password' => 'somepassword',
			'root' => 'someroot'
		);

		// add mount point as "test" user 
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// check mount points in the perspective of user $testUser
		\OC_User::setUserId($testUser);

		$mountPoints = OC_Mount_Config::getAbsoluteMountPoints($testUser);
		if ($expectVisible) {
			$this->assertEquals(1, count($mountPoints));
			$this->assertTrue(isset($mountPoints['/' . self::TEST_USER1 . '/files/ext']));
			$this->assertEquals('\OC\Files\Storage\SMB', $mountPoints['/' . self::TEST_USER1 . '/files/ext']['class']);
			$this->assertEquals($mountConfig, $mountPoints['/' . self::TEST_USER1 . '/files/ext']['options']);
		}
		else {
			$this->assertEquals(0, count($mountPoints));
		}
	}
}
