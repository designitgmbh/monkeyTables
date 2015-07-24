# monkeyTables

monkeyTables are a set of PHP and JavaScript libraries with the necessary styling, to provide you with a simple and effective package, that will allow you to create powerful tables directly made from your database.
It brings a lot of nice features including
- automatic filtering 
- sorting
- presets
- inline editing
- tabbing
- and much more..
 
## Installation

To install the package, you just let composer do the work for you:   
```composer.phar require "designitgmbh/monkey-tables":"dev-master"```

### User model

Because we offer presets, which are unique per user and can be restricted, monkeyTables need to access a User Model, which has a relation to a profile from MonkeyAccess.
```PHP
<?php

namespace App\Models;

class User extends SystemUser {
 	public function profile()
	{
		return $this->belongsTo('Designitgmbh\MonkeyAccess\Models\Profile', 'profile_id');
	}
}
```

### Lumen

This package is compatible with Lumen, but you will have to do some minimal changes:

1. Enable Facades
2. Enable Eloquent
3. Install larasupport: https://github.com/irazasyed/larasupport

## Usage

### Backend

In a controller, you can build up your table, and send it back as JSON.
```PHP
$mTable = new mTable;
$mTable
	->setRequest(Request::all())
	->source('Project');

$mTable->add(
	(new mTableColumn("#", "id"))
)->add(
	(new mTableColumn("Name", "name"))
		->setClickable("/project/{{ID}}", "id")		
)->add(
	(new mTableColumn("Department", "department->name"))
		->setClickable("/department/{{ID}}", "department->id")
)->add(
	(new mTableColumn("Unit", "unit->name"))
		->setClickable("/unit/{{ID}}", "unit->id")
);

return response()->json($mTable->render());
```

Don't forget to add a route to the controller.
```PHP
$app->post('/project/indexList', 'ProjectController@indexList');
```

### Frontend

After including all dependencies, you just need to add a div, which will hold the table, and a small javascript snippet.

```HTML
<div id="mtable"></div>

<script>
	var frame = new mTableFrameStd("#mtable", {});
	var table = new mTableStd({
		frame: frame,
		url: "/project/indexList"
	});

	frame.addTable(table);
	frame.show();
</script>
```
