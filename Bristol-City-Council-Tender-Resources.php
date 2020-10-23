<!DOCTYPE html>
<html>
    <head>
        <link href="packages/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="packages/dojo-release-1.9.1/dojo/dojo.js"></script>
        <link href="packages/dojo-release-1.9.1/dojo/resources/dojo.css" rel="stylesheet" type="text/css" />
        <link href="packages/dojo-release-1.9.1/dojox/grid/resources/claroGrid.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <div class="container-fluid">
            <h2>Tenders obtained from <a href="http://data.gov.uk">data.gov.uk</a></h2>
            <h3>Contracts and Tenders from Bristol City Council</h3>

            <?php 
            $file = fopen("http://data.gov.uk/api/2/rest/package/bristol-city-council-contracts-tenders", 'r');
            $string = fgets($file);
            $obj = json_decode($string);
            fclose($file);
            $js_parsed= array(); $content = array(); $csv = array();
            $count = 0; $tender_count = array(); $amount = array();
            $proc = array('GO08', 'WB01', 'SB93', 'SB19', 'GO07', 'CC01', 'WB41', 'SB99', 'SB39', 'SB45', 'SB36', 'SB34', 'SE22', 'SB94', 'SB99A', 'SE70', 'SE71');
            
            function filter_using_keys($input) {
                return !($input == '');
            }
            
            function filter_using_id($input) {
                return !($input == "");
            }
            
            foreach($obj->resources as $month_count => $resource) {
                $file = fopen($resource->url, 'r');
                $csv['fields'] = fgetcsv($file);
                if(is_array($csv['fields'])):
                    $csv['fields'] = array_filter($csv['fields'], 'filter_using_keys');
                    if($csv['fields'][0] == 'Body Name'):
                        $tender_count[$month_count] = 0;
                        $amount[$month_count] = 0;
                        while(!feof($file)) {
                            $csv['values'] = fgetcsv($file);
                            if(is_array($csv['values'])) {
                                $csv['values'] = array_slice($csv['values'], 0, count($csv['fields']));
                                if(!empty($csv['values'][count($csv['fields']) - 1])):
                                    $content = array_combine($csv['fields'], $csv['values']);
                                    $fmt = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
                                    $fmt_parse = $fmt->parse($content['Amount']);
                                    $amount[$month_count] += (float) $fmt_parse;
                                    $tender_count[$month_count]++;
//                                    global $proc;
//                                    foreach($proc as $keyword) {
//                                        $keyword_mod = $keyword . ' -';
//                                        $keyword_mod2 = $keyword . ' :';
//                                        $proc_data = str_getcsv($content['ProcurementCategory'], ', ');
//                                        foreach($proc_data as $data) {
//                                            if((strpos($data, $keyword_mod) !== FALSE) || (strpos($data, $keyword_mod2) !== FALSE)) {
//                                                $content['ResourceURL'] = $resource->url;
//                                                $js_parsed[$count][] = $content;
//                                                break;
//                                            }
//                                        }
//                                        
//                                    }
                                    $js_parsed[$count][] = $content;
                                endif;
                            }
                        }
                        
                    endif;
                endif;
                $count++;
                fclose($file);
            }
            
            ?>

            <?php $count = -1;
            foreach($obj->resources as $resource) { 
                $count++;
            ?>
            <div>
                <h3><?php echo $resource->description; ?></h3>
                <br />
                <div class="clearfix">
                    <div id="row-table-<?php echo $count; ?>" class="row-fluid">
                        <p>Data Not Available</p>
                    </div>
                </div>
                <br />
            </div>
            <?php } ?>
            
        </div>
        
        <script type="text/javascript">
            var data = {
                identifier: "id",
                items: []
            };
            
            var table_data = [], result_data = [];
            
            var tender_count = <?php echo json_encode($tender_count); ?>;
            
            var amount = <?php echo json_encode($amount); ?>;
            
            var js_parsed = <?php echo json_encode($js_parsed); ?>;
            
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
            
            
//            var DataGrid = dojo.require("dojox/grid/DataGrid");
//            var ItemFileWriteStore = dojo.require("dojo/data/ItemFileWriteStore");
//            var number = dojo.require("dojo/number");
//            var dojoxObject = dojo.require("dojox/lang/functional/object");
//            var dojoxArray = dojo.require("dojox/lang/functional/array");
//            var nodeListDom = dojo.require("dojo/NodeList-dom");
//            var nodeListManipulate = dojo.require("dojo/NodeList-manipulate");
//            var nodeListTraverse = dojo.require("dojo/NodeList-traverse");
            
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
            
//            load(DataGrid, ItemFileWriteStore, number, dojoxObject, dojoxArray, nodeListDom, nodeListManipulate, nodeListTraverse);
        </script>
        
    </body>
</html>