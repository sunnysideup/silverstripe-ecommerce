<?php

class ClearOldCarts extends BuildTask{

	protected $title = "Clear Old Carts";

	protected $description = "deletes old unsubmitted carts";

	function run($request){
		CartCleanupTask::run_on_demand();
	}

}
