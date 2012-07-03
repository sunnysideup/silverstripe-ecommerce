<?php
/**
 * extends the standard RestfulServer to:
 * - provide better access to extended classes
 *
 * see: http://api.silverstripe.org/2.4/sapphire/api/RestfulServer.html
 *
 * You can show JSON by hacking: RestfulServer::getDataFormatter
 * NOTE: JSON IS NOT AVAILABLE YET WITHIN RESTFUL SERVER
 * @todo:
 * - fix http://site/api/ecommerce/v1/Order/123/BillingAddress.xml
 * - fix http://site/api/ecommerce/v1/Order/123/ShippingAddress.xml
 * - fix http://site/api/ecommerce/v1/Order/123/Member.xml
 */






class EcommerceRestfulServer extends RestfulServer {

	function index() {
		XMLDataFormatter::$api_base = 'api/ecommerce/v1/';
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
			if($obj) {
				$className = $this->urlParams['ClassName'] = $obj->ClassName;
			}
			else {
				return $this->notFound();
			}
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
