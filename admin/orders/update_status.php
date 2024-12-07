<div class="container-fluid">
    <form id="status-update-form">
        <input type="hidden" name="id" value="<?php echo $_GET['oid'] ?>">
        <div class="form-group">
            <label for="" class="control-label">Trạng Thái</label>
            <select name="status" id="" class="custom-select custol-select-sm">
                <option value="0" <?php echo $_GET['status'] == 0 ? "selected" : '' ?>>Đang Xử Lý</option>
                <option value="1" <?php echo $_GET['status'] == 1 ? "selected" : '' ?>>Đã Đóng Hàng</option>
                <option value="2" <?php echo $_GET['status'] == 2 ? "selected" : '' ?>>Đang Giao Hàng</option>
                <option value="3" <?php echo $_GET['status'] == 3 ? "selected" : '' ?>>Đã Giao Hàng</option>
                <option value="4" <?php echo $_GET['status'] == 4 ? "selected" : '' ?>>Đã Hủy</option>
            </select>
        </div>
    </form>
</div>
<script>
    $(function() {
        $('#status-update-form').submit(function(e) {
            e.preventDefault();
            start_loader()
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=update_order_status",
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                error: err => {
                    console.log(err)
                    alert_toast("Lỗi", "error")
                    end_loader()
                },
                success: function(resp) {
                    if (!!resp.status && resp.status == 'success') {
                        location.reload()
                    } else {
                        console.log(resp)
                        alert_toast("Lỗi", "error")
                        end_loader()
                    }
                }
            })
        })
    })
</script>