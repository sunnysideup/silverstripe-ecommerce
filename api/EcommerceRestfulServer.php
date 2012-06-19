<?php

class EcommerceRestfulServer extends RestfulServer {
	
	function index() {
		XMLDataFormatter::$api_base = 'api/ecommerce/';
		if(!isset($this->urlParams['ClassName'])) return $this->notFound();
		$className = $this->urlParams['ClassName'];
		$id = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : null;
		$relation = (isset($this->urlParams['Relation'])) ? $this->urlParams['Relation'] : null;
		
		// Check input formats
		if(!class_exists($className)) return $this->notFound();
		if($id && !is_numeric($id)) return $this->notFound();
		if($relation && !preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $relation)) return $this->notFound();
		
		// fix
		if($id) {
			$obj = DataObject::get_by_id($className, $id);
			$className = $this->urlParams['ClassName'] = $obj->ClassName;
		}
		
		// if api access is disabled, don't proceed
		$apiAccess = singleton($className)->stat('api_access');
		if(!$apiAccess) return $this->permissionFailure();

		// authenticate through HTTP BasicAuth
		$this->member = $this->authenticate();

		// handle different HTTP verbs
		if($this->request->isGET() || $this->request->isHEAD()) return $this->getHandler($className, $id, $relation);
		if($this->request->isPOST()) return $this->postHandler($className, $id, $relation);
		if($this->request->isPUT()) return $this->putHandler($className, $id, $relation);
		if($this->request->isDELETE()) return $this->deleteHandler($className, $id, $relation);

		// if no HTTP verb matches, return error
		return $this->methodNotAllowed();
	}
}