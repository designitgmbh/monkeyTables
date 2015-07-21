# monkeyTables

monkeyTables are a set of PHP and JavaScript libraries with the necessary styling, to provide you with a simple and effective package, that will allow you to create powerful tables directly made from your database.
It brings a lot of nice features including
- automatic filtering 
- sorting
- presets
- inline editing
- tabbing
- and much more..

## Usage

### Backend

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

$mTablesFrameworkHelperController 	= new mTablesFrameworkHelperController;
$mTablesFrameworkDBController 		= new mTablesFrameworkDBController;

return response()->json($mTable->render(
	$mTablesFrameworkHelperController, 
	$mTablesFrameworkDBController
));
```

### Frontend

After including all dependencies, you just need to add a div, which will hold the table, and a small javascript snippet.

```HTML
<div id="mtable"></div>

<script>
	var frame = new mTableFrameStd("#mtable", {});
	var table = new mTableStd({
		frame: frame,
		url: "/oTableBackend/project/indexList"
	});

	frame.addTable(table);
	frame.show();
</script>
```
