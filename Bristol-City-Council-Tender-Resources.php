<!DOCTYPE html>
<html>
    <head>
        <link href="packages/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="packages/dojo-release-1.9.1/dojo/dojo.js"></script>
        <link href="packages/dojo-release-1.9.1/dojo/resources/dojo.css" rel="stylesheet" type="text/css" />
        <link href="packages/dojo-release-1.9.1/dojox/grid/resources/claroGrid.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <div id="container-box" class="container-fluid">
            <h2>Tenders obtained from <a href="http://data.gov.uk">data.gov.uk</a></h2>
            <h3>Contracts and Tenders from Bristol City Council</h3>
        </div>
        
        <script type="text/javascript">
            var data = {
                identifier: "id",
                items: []
            };
            
            var table_data = [], result_data = [], tender_count = {}, amount = {}, js_parsed = {}, api = null, fetch = 0;
            
            require(["dojo/request"], function(request){
                request("json_parsed.json").then(
                    function(text){
                        js_parsed = JSON.parse(text);
                        fetch++;
                    },
                    function(error){
                        console.log("An error occurred: " + error);
                    }
                );
                request("amount.json").then(
                    function(text){
                        amount = JSON.parse(text);
                        fetch++;
                    },
                    function(error){
                        console.log("An error occurred: " + error);
                    }
                );
                request("tender_count.json").then(
                    function(text){
                        tender_count = JSON.parse(text);
                        fetch++;
                    },
                    function(error){
                        console.log("An error occurred: " + error);
                    }
                );
                request('api.json').then(function(text) {
                    api = JSON.parse(text);
                    fetch++;
                },
                function(error) {
                    console.log("An error occurred: " + error);
                });
            });
            
            var interval = setInterval(function() {
                if(fetch == 4) {
                    (function() {
                        if(typeof api == 'object') {
                            require(["dojox/lang/functional/object", "dojox/lang/functional/array", "dojo/NodeList-manipulate"], function(dojoxObject, dojoxArray, nodeListObject) {
                                var nodeList = new nodeListObject(document.getElementById('container-box'));
                                dojoxArray.forEach(api['resources'], function(resource, key) {
                                    nodeList.append('<div><h3>' + resource['description'] + '</h3><br /><div class="clearfix"><div id="row-table-' + key + '" class="row-fluid"><p>Data Not Available</p></div></div><br /></div>');
                                });
                            });
                        }

                        function set_fields(fields) {
                            new_fields = [];
                            dojo.forEach(fields, function(field, key) {
                                new_fields[key] = {
                                    'name' : field,
                                    'field': field,
                                    'width': '150px'
                                };
                            });
                            return new_fields;
                        }

                        var evaluate = document.createElement('div');

                        require([ "dojox/grid/DataGrid", "dojo/data/ItemFileWriteStore", "dojo/number", "dojox/lang/functional/object", "dojox/lang/functional/array","dojo/NodeList-dom", "dojo/NodeList-manipulate", "dojo/NodeList-traverse" ], function(DataGrid, ItemFileWriteStore, number, dojoxObject, dojoxArray, nodeListDom, nodeListManipulate, nodeListTraverse) {
                            dojoxArray.forEach(js_parsed, function(month, index) { 
                                var fields = dojoxObject.keys(month[0]);
                                fields = set_fields(fields);
                                value = month;
                                dojo.forEach(value, function(item, key) {
                                    value[key] = dojo.mixin(value[key], { 'id': key });
                                });
                                var data_index = number.parse(index);
                                table_data[data_index] = dojo.mixin({}, data);
                                table_data[data_index].items = value;
                                var id = 'row-table-' + index;
                                var grid = new DataGrid({
                                    store: new ItemFileWriteStore({ data: table_data[data_index] }),
                                    autoHeight: true,
                                    structure: fields
                                }, id);
                                grid.startup();
                                var nodeList = new nodeListManipulate(document.getElementById(id));
                                var evaluateList = new nodeListDom(evaluate);
                                var month_amount = 0, month_number = 0;
                                dojo.forEach(table_data[data_index].items, function(item, key) {
                                    month_amount += number.parse(item["Amount"][0]);
                                    month_number++;
                                });
                                result_data[data_index] = {
                                    "amount_tender_scope": month_amount,
                                    "amount_tender_month": number.parse(amount[index]),
                                    "number_tender_scope": month_number,
                                    "number_tender_month": number.parse(tender_count[index])
                                };
                                evaluateList.addClass("row-fluid");
                                evaluateList.attr('id', "row-evaluated-" + index);
                                evaluateList.html('<p>Total Amount spend for tenders in scope: <span class="amount_tender_scope">' + month_amount + '</span></p>');
                                evaluateList.append('<p>Total Number of tenders in scope: <span class="number_tender_scope">' + month_number + '</span></p>');
                                evaluateList.append('<p>Total Amount spend for tenders in the month: <span class="amount_tender_month">' + amount[index] + '</span></p>');
                                evaluateList.append('<p>Total Number of tenders found in the month: <span class="number_tender_month">' + tender_count[index] + '</span></p>');
                                evaluateList.append('<p>Percentage of tenders in scope: <span class="perc_tender_scope">' + result_data[data_index]["number_tender_scope"] / result_data[data_index]["number_tender_month"] * 100 + '</span></p>');
                                evaluateList.append('<p>Percentage of amount in scope: <span class="perc_amount_scope">' + result_data[data_index]["amount_tender_scope"] / result_data[data_index]["amount_tender_month"] * 100 + '</span></p>');
                                evaluateList.wrap("<content></content>");
                                var wrapList = new nodeListTraverse(evaluateList);
                                nodeList.after(wrapList.parent("content").innerHTML());
                            });
                        });
                    })();
                    
                    window.clearInterval(interval);
                }
            }, 50);
            
        </script>
        
    </body>
</html>