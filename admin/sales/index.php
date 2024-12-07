<style>
    table td,
    table th {
        padding: 3px !important;
    }
</style>
<?php
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] :  date("Y-m-d", strtotime(date("Y-m-d") . " -7 days"));
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] :  date("Y-m-d");
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h5 class="card-title">Báo cáo bán hàng</h5>
    </div>
    <div class="card-body">
        <form id="filter-form">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label for="date_start">Ngày bắt đầu</label>
                    <input type="date" class="form-control form-control-sm" name="date_start" value="<?php echo date("Y-m-d", strtotime($date_start)) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="date_start">Ngày kết thúc</label>
                    <input type="date" class="form-control form-control-sm" name="date_end" value="<?php echo date("Y-m-d", strtotime($date_end)) ?>">
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-flat btn-block btn-primary btn-sm"><i class="fa fa-filter"></i> Lọc</button>
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-flat btn-block btn-success btn-sm" type="button" id="printBTN"><i class="fa fa-print"></i> In</button>
                </div>
            </div>
        </form>
        <hr>
        <div id="printable">
            <div class="row row-cols-2 justify-content-center align-items-center" id="print_header" style="display:none">
                <div class="col-1">
                    <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="<?php echo $_settings->info('short_name') ?>" width="75px" heigth="75px">
                </div>
                <div class="col-7">
                    <h4 class="text-center m-0"><?php echo $_settings->info('name') ?></h4>
                    <h3 class="text-center m-0"><b>Báo cáo bán hàng</b></h3>
                    <?php if ($date_start != $date_end): ?>
                        <p class="text-center m-0">Date Between <?php echo date("M d,Y", strtotime($date_start)) ?> and <?php echo date("M d,Y", strtotime($date_end)) ?></p>
                    <?php else: ?>
                        <p class="text-center m-0">As of <?php echo date("M d,Y", strtotime($date_start)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <hr>

            <table class="table table-bordered">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="20%">
                    <col width="20%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ngày Tạo</th>
                        <th>Sản Phẩm</th>
                        <th>Khách Hàng</th>
                        <th>Phương Thức Thanh Toán</th>
                        <th>Giá</th>
                        <th>Số Lượng</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $gtotal = 0;
                    $qry = $conn->query("SELECT * FROM `sales` WHERE DATE(date_created) BETWEEN '{$date_start}' AND '{$date_end}' ORDER BY unix_timestamp(date_created) DESC");
                    while ($row = $qry->fetch_assoc()):
                        $olist = $conn->query("SELECT ol.*, p.name AS pname, b.name AS bname, CONCAT(c.firstname,' ',c.lastname) AS client_name, c.email, o.date_created, cc.category, i.variant, o.payment_method 
                                FROM order_list ol 
                                INNER JOIN orders o ON o.id = ol.order_id 
                                INNER JOIN inventory i ON ol.inventory_id = i.id 
                                INNER JOIN `products` p ON p.id = i.product_id 
                                INNER JOIN clients c ON c.id = o.client_id 
                                INNER JOIN brands b ON p.brand_id = b.id 
                                INNER JOIN categories cc ON p.category_id = cc.id 
                                WHERE ol.order_id = '{$row['order_id']}'");
                        while ($roww = $olist->fetch_assoc()):
                            $gtotal += $roww['quantity'] * $roww['price'];
                    ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo $row['date_created']; ?></td>
                                <td>
                                    <p class="m-0"><?php echo $roww['pname'] . " - " . $roww['variant']; ?></p>
                                    <p class="m-0"><small>Brand: <?php echo $roww['bname']; ?></small></p>
                                    <p class="m-0"><small>Category: <?php echo $roww['category']; ?></small></p>
                                </td>
                                <td>
                                    <p class="m-0"><?php echo $roww['client_name']; ?></p>
                                    <p class="m-0"><small>Email: <?php echo $roww['email']; ?></small></p>
                                </td>
                                <td><?php echo strtoupper($roww['payment_method']); ?></td>
                                <td class="text-center"><?php echo format_num($roww['price']); ?></td>
                                <td class="text-center"><?php echo format_num($roww['quantity']); ?></td>
                                <td class="text-right"><?php echo format_num($roww['quantity'] * $roww['price']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endwhile; ?>
                    <?php if ($qry->num_rows <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="8">No Data...</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="7" class="font-weight-bold text-center">TOTAL SALES</th>
                        <th class="text-right font-weight-bold"><?= format_num($gtotal) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<noscript>
    <style>
        .m-0 {
            margin: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .table {
            border-collapse: collapse;
            width: 100%
        }

        .table tr,
        .table td,
        .table th {
            border: 1px solid gray;
        }
    </style>
</noscript>
<script>
    $(function() {
        $('table th, table td').addClass("px-1 py-2 align-middle")
        $('#filter-form').submit(function(e) {
            e.preventDefault()
            location.href = "./?page=sales&date_start=" + $('[name="date_start"]').val() + "&date_end=" + $('[name="date_end"]').val()
        })

        $('#printBTN').click(function() {
            var head = $('head').clone();
            var rep = $('#printable').clone();
            var ns = $('noscript').clone().html();
            start_loader()
            rep.prepend(ns)
            rep.prepend(head)
            rep.find('#print_header').show()
            var nw = window.document.open('', '_blank', 'width=900,height=600')
            nw.document.write(rep.html())
            nw.document.close()
            setTimeout(function() {
                nw.print()
                setTimeout(function() {
                    nw.close()
                    end_loader()
                }, 200)
            }, 300)
        })
    })
</script>