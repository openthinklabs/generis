<?php

error_reporting(E_ALL);

require_once dirname(__FILE__).'/../common/common.php';
require_once INCLUDES_PATH.'/simpletest/autorun.php';

	
class UserServiceTestCase extends UnitTestCase {
	
	protected $service;
	
	public function setUp(){
		$this->service = core_kernel_users_Service::singleton();
	}


	
	public function testLogin(){
		$this->assertTrue($this->service->login(SYS_USER_LOGIN, SYS_USER_PASS,CLASS_ROLE_TAOMANAGER));
		$this->assertTrue($this->service->login(SYS_USER_LOGIN, SYS_USER_PASS,CLASS_ROLE_BACKOFFICE));
	}
	
	public function testLoginExists(){
		$this->assertTrue($this->service->loginExists(SYS_USER_LOGIN));
		$this->assertFalse($this->service->loginExists('toto'));
	}
	
	public function testAddRole(){
		core_kernel_impl_ApiModelOO::singleton()->login(SYS_USER_LOGIN, SYS_USER_PASS, DATABASE_NAME, CLASS_ROLE_TAOMANAGER);
		$role1 = $this->service->addRole('testAddRoleTESt 1','testAddRole 1');
		$subClassProp = new core_kernel_classes_Property(RDF_SUBCLASSOF);
		$typeProp = new core_kernel_classes_Property(RDF_TYPE);
		$this->assertTrue($role1->getOnePropertyValue($subClassProp)->uriResource == CLASS_GENERIS_USER);

		$backoffice = new core_kernel_classes_Resource(CLASS_ROLE_BACKOFFICE);
		$role2 = $this->service->addRole('testAddRoleTESt 2','test2',$backoffice);
		$this->assertTrue($role2->getOnePropertyValue($subClassProp)->uriResource == CLASS_GENERIS_USER);
		$this->assertTrue($role1->getOnePropertyValue($typeProp)->uriResource == CLASS_ROLE);
		$this->assertTrue($role2->getOnePropertyValue($typeProp)->uriResource == $backoffice->uriResource);
	
		$role1->delete();
		$role2->delete();
		

	}
	
	public function testAddUser(){
		core_kernel_impl_ApiModelOO::singleton()->login(SYS_USER_LOGIN, SYS_USER_PASS, DATABASE_NAME, CLASS_ROLE_TAOMANAGER);
		$role1 = $this->service->addRole('addUserFakeRole','addUserFakeRole');
		$user = $this->service->addUser('toto',md5('toto'),$role1);
		$this->assertTrue($this->service->loginExists('toto'));
		$this->assertTrue($this->service->logout());
		$this->assertTrue($this->service->login('toto', md5('toto'),$role1->uriResource));
		$user->delete();
		$role1->delete();
	}
	
	
	public function testLoginApi(){
	   
		$this->assertTrue($this->service->loginApi('http://www.tao.lu/Ontologies/TAO.rdf#installator'));
	}
	
}