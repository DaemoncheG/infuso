<? 

$orders = eshop::orders()->eq("date(sent)",util::now()->notime());

<b>Заказы за сегодня:</b> {$orders->count()}

<div class='nc1ddkrmnt' >
    foreach($orders as $order) {
        <a href='{$order->editor()->url()}' class='order' >
            foreach($order->items() as $item) {
                $preview = $item->item()->photo()->preview(32,32)->fit();
                <img src='$preview' />
            }
        </a>
    }
</div>

$table = reflex_table::factoryByName("eshop_order")->prefixedName();
reflex_mysql::query("select date(`sent`) as `date`, count(*) as `count`, sum(`total`) as `total` from `$table` where TIMESTAMPDIFF(DAY,`sent`,now()) < 100 and `status`!='eshop_order_status_cancelled' group by `date`;");


$r = reflex_mysql::get_col("count","date");

$chart = new google_chart();
$chart->col("date","string");
$chart->col("orders");
$chart->height(70);
$chart->columnChart();
$chart->title("Заказов / день");

$cols = array();
for($i=-30;$i<=0;$i++) {
    $date = util::now()->shift(3600*24*$i)->notime()."";
    $count = $r[$date];
    $chart->row($date,$count*1);
}
    
$chart->exec();

$r = reflex_mysql::get_col("total","date");

$chart = new google_chart();
$chart->col("date","string");
$chart->col("orders");
$chart->columnChart();
$chart->height(70);
$chart->title("Объем продаж / день");

$cols = array();
for($i=-30;$i<=0;$i++) {
    $date = util::now()->shift(3600*24*$i)->notime()."";
    $count = $r[$date];
    $chart->row($date,$count*1);
}
    
$chart->exec();
