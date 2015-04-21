<?php

?>
<html>
<head>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="http://cdn.datatables.net/1.10.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="http://datatables.net/release-datatables/extensions/TableTools/css/dataTables.tableTools.css">
<link rel="stylesheet" type="text/css" href="https://datatables.net/release-datatables/extensions/ColVis/css/dataTables.colVis.css">


<!-- jQuery -->
<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.10.2.min.js"></script>
  
<!-- DataTables -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="http://datatables.net/release-datatables/extensions/TableTools/js/dataTables.tableTools.js"></script>
<script type="text/javascript" charset="utf8" src="https://datatables.net/release-datatables/extensions/ColVis/js/dataTables.colVis.js"></script>


</head>
<body>
<table id="example" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Consequences</th>
                <th>Causes</th>
            </tr>
        </thead>
 
        <tfoot>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Consequences</th>
                <th>Causes</th>
            </tr>
        </tfoot>
 

    </table>
    <script type="text/javascript">
    $(document).ready(function() {
        
        var table = $('#example').dataTable({
            "ajax":'?post_type=risk&feed=allRisks',
        	"dom": 'TC<"clear">lfrtip',
            "columns": [
                        { "data": "title" },
                        { "data": "description" },
                        { "data": "consequences" },
                        { "data": "causes" }
                    ],
           "columnDefs": [ {
                  "targets": 1,
                  "data": null,
                  "defaultContent": "<button>Click!</button>"
            } ],
            "tableTools": {
                "sRowSelect": "os",
                "aButtons": [ "select_all", "select_none" ],
                "fnRowSelected": function ( nodes ) {
                    alert( 'The row with ID '+nodes[0].id+' was selected' );
                }
            },
            "colVis": {
                exclude: [ 0 ]
            },
        	 "aoColumnDefs": [ {
	        	 "aTargets": [3],
				 "fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {	 
			          $(nTd).css('text-align', 'center')
			     }
        	 }]
            }); 
         $("#example tbody tr").on("click", function(event){
                var id = table.fnGetData(this)[0];
                alert(id);
                });

        

    } );
    </script>
    </body>
    </html>