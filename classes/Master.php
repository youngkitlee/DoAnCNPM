<?php
require_once('../config.php');
class Master extends DBConnection
{
	private $settings;
	public function __construct()
	{
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct()
	{
		parent::__destruct();
	}
	function capture_err()
	{
		if (!$this->conn->error)
			return false;
		else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_brand()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$v = addslashes(trim($v));
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `brands` where `name` = '{$name}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Tên thương hiệu đã tồn tại.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `brands` set {$data} ";
		} else {
			$sql = "UPDATE `brands` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if ($save) {
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if (empty($id))
				$resp['msg'] = "Thương hiệu mới đã được lưu thành công.";
			else
				$resp['msg'] = "Đã cập nhật thương hiệu thành công.";
			if (!empty($_FILES['img']['tmp_name'])) {
				if (!is_dir(base_app . "uploads/brands"))
					mkdir(base_app . "uploads/brands");
				$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$fname = "uploads/brands/$bid.$ext";
				$accept = array('image/jpeg', 'image/png');
				if (!in_array($_FILES['img']['type'], $accept)) {
					$resp['msg'] .= " Loại tệp hình ảnh không hợp lệ";
				}
				if ($_FILES['img']['type'] == 'image/jpeg')
					$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name']);
				elseif ($_FILES['img']['type'] == 'image/png')
					$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name']);
				if (!$uploadfile) {
					$resp['msg'] .= " Hình ảnh không hợp lệ";
				}
				$temp = imagescale($uploadfile, 200, 200);
				if (is_file(base_app . $fname))
					unlink(base_app . $fname);
				if ($_FILES['img']['type'] == 'image/jpeg')
					$upload = imagejpeg($temp, base_app . $fname);
				elseif ($_FILES['img']['type'] == 'image/png')
					$upload = imagepng($temp, base_app . $fname);
				else
					$upload = false;
				if ($upload) {
					$qry = $this->conn->query("UPDATE brands set `image_path` = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$bid}' ");
				}
				imagedestroy($temp);
			}
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_brand()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `brands` set `delete_flag` = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Đã xóa thương hiệu thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_category()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (isset($_POST['description'])) {
			if (!empty($data)) $data .= ",";
			$data .= " `description`='" . addslashes(htmlentities($description)) . "' ";
		}
		$check = $this->conn->query("SELECT * FROM `categories` where `category` = '{$category}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Danh mục đã tồn tại.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `categories` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `categories` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "Danh mục mới đã được lưu thành công.");
			else
				$this->settings->set_flashdata('success', "Đã cập nhật danh mục thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_category()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `categories` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Đã xóa thành công danh mục.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function save_product()
	{
		// Chuyển đổi các ký tự đặc biệt thành thực thể HTML để bảo vệ dữ liệu
		$_POST['specs'] = htmlentities($_POST['specs']);

		// Sử dụng prepared statements thay vì addslashes và real_escape_string để bảo vệ chống SQL Injection
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$name = isset($_POST['name']) ? $this->conn->real_escape_string($_POST['name']) : '';

		$data = "";
		foreach ($_POST as $k => $v) {
			if ($k == 'id') {
				continue;
			}
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$escaped_value = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$escaped_value}' ";
			}
		}

		// Kiểm tra xem sản phẩm đã tồn tại chưa
		$check = $this->conn->query("SELECT * FROM `products` WHERE `name` = '{$name}' " . (!empty($id) ? " AND id != {$id} " : ""))->num_rows;
		if ($this->capture_err()) {
			return $this->capture_err();
		}
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Sản phẩm đã tồn tại.";
			return json_encode($resp);
		}

		// Chọn câu lệnh SQL phù hợp (INSERT hoặc UPDATE)
		if (empty($id)) {
			$sql = "INSERT INTO `products` SET {$data}";
		} else {
			$sql = "UPDATE `products` SET {$data} WHERE id = '{$id}'";
		}

		// Thực hiện truy vấn SQL
		$save = $this->conn->query($sql);
		if ($save) {
			$pid = empty($id) ? $this->conn->insert_id : $id;
			$upload_path = "uploads/product_" . $pid;
			if (!is_dir(base_app . $upload_path)) {
				if (!mkdir(base_app . $upload_path, 0755, true)) { // Thêm quyền và tạo thư mục đệ quy nếu cần
					$resp['status'] = 'failed';
					$resp['msg'] = 'Không thể tạo thư mục upload.';
					return json_encode($resp);
				}
			}

			if (isset($_FILES['img']) && is_array($_FILES['img']['tmp_name']) && count($_FILES['img']['tmp_name']) > 0) {
				$err = "";
				foreach ($_FILES['img']['tmp_name'] as $k => $v) {
					if (!empty($_FILES['img']['tmp_name'][$k])) {
						$accept = array('image/jpeg', 'image/png');
						$file_type = $_FILES['img']['type'][$k];
						if (!in_array($file_type, $accept)) {
							$err = "Loại tệp hình ảnh không hợp lệ";
							break;
						}

						// Xác định đường dẫn lưu hình ảnh
						$original_name = basename($_FILES['img']['name'][$k]);
						$spath = base_app . $upload_path . '/' . $original_name;
						$i = 0;
						while (is_file($spath)) {
							$spath = base_app . $upload_path . '/' . $i . "_" . $original_name;
							$i++;
						}

						// Lưu hình ảnh mà không thay đổi gì
						if (!move_uploaded_file($v, $spath)) {
							$err = "Không thể lưu ảnh gốc.";
							break;
						}
					}
				}
				if (!empty($err)) {
					$resp['status'] = 'failed';
					$resp['msg'] = 'Sản phẩm đã được lưu thành công nhưng ' . $err;
					$resp['id'] = $pid;
					return json_encode($resp);
				}
			}

			// Thiết lập phản hồi thành công
			$resp['status'] = 'success';
			if (empty($id)) {
				$this->settings->set_flashdata('success', "Đã thêm sản phẩm mới thành công.");
			} else {
				$this->settings->set_flashdata('success', "Sản phẩm đã được cập nhật thành công.");
			}
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}


	function delete_product()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `products` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Sản phẩm đã được xóa thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function delete_img()
	{
		extract($_POST);
		if (is_file($path)) {
			if (unlink($path)) {
				$resp['status'] = 'success';
			} else {
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete ' . $path;
			}
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown ' . $path . ' path';
		}
		return json_encode($resp);
	}
	function save_inventory()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `inventory` where `product_id` = '{$product_id}' and variant = '{$variant}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Sản phẩm này trong kho đã tồn tại.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `inventory` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `inventory` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "Sản phẩm mới đã được thêm vào kho thành công.");
			else
				$this->settings->set_flashdata('success', "Sản phẩm trong kho được cập nhật thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_inventory()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `inventory` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Đã xóa sản phẩm trong kho thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function register()
	{
		extract($_POST);
		$data = "";
		$_POST['password'] = md5($_POST['password']);
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `clients` where `email` = '{$email}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email đã được sử dụng.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `clients` set {$data} ";
		} else {
			$sql = "UPDATE `clients` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if ($save) {
			$cid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "Tài khoản được tạo thành công.");
			else
				$this->settings->set_flashdata('success', "Tài khoản được tạo thành công.");
			$this->settings->set_userdata('login_type', 2);
			foreach ($_POST as $k => $v) {
				$this->settings->set_userdata($k, $v);
			}
			$this->settings->set_userdata('id', $cid);
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function add_to_cart()
	{
		extract($_POST);
		$data = " client_id = '" . $this->settings->userdata('id') . "' ";
		$_POST['price'] = str_replace(",", "", $_POST['price']);
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `cart` where `inventory_id` = '{$inventory_id}' and client_id = " . $this->settings->userdata('id'))->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$sql = "UPDATE `cart` set quantity = quantity + {$quantity} where `inventory_id` = '{$inventory_id}' and client_id = " . $this->settings->userdata('id');
		} else {
			$sql = "INSERT INTO `cart` set {$data} ";
		}

		$save = $this->conn->query($sql);
		if ($this->capture_err())
			return $this->capture_err();
		if ($save) {
			$resp['status'] = 'success';
			$resp['cart_count'] = $this->conn->query("SELECT SUM(quantity) as items from `cart` where client_id =" . $this->settings->userdata('id'))->fetch_assoc()['items'];
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function update_cart_qty()
	{
		extract($_POST);

		$save = $this->conn->query("UPDATE `cart` set quantity = '{$quantity}' where id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($save) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function empty_cart()
	{
		$delete = $this->conn->query("DELETE FROM `cart` where client_id = " . $this->settings->userdata('id'));
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_cart()
	{
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `cart` where id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_order()
	{
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `orders` where id = '{$id}'");
		$delete2 = $this->conn->query("DELETE FROM `order_list` where order_id = '{$id}'");
		$delete3 = $this->conn->query("DELETE FROM `sales` where order_id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Đơn hàng đã được xóa thành công");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function place_order()
	{
		if (empty($id)) {
			$prefix = date("Ym");
			$code = sprintf("%'.05d", 1);
			while (true) {
				$check = $this->conn->query("SELECT * FROM `orders` where ref_code = '{$prefix}{$code}' ")->num_rows;
				if ($check > 0) {
					$code = sprintf("%'.05d", ceil($code) + 1);
				} else {
					break;
				}
			}
			$_POST['ref_code'] = $prefix . $code;
		}
		extract($_POST);
		$client_id = $this->settings->userdata('id');

		$data = " client_id = '{$client_id}' ";
		if (isset($ref_code))
			$data .= " ,ref_code = '{$ref_code}' ";
		$data .= " ,payment_method = '{$payment_method}' ";
		$data .= " ,amount = '{$amount}' ";
		$data .= " ,paid = '{$paid}' ";
		$data .= " ,delivery_address = '{$delivery_address}' ";
		$order_sql = "INSERT INTO `orders` set $data";
		$save_order = $this->conn->query($order_sql);
		if ($this->capture_err())
			return $this->capture_err();
		if ($save_order) {
			$order_id = $this->conn->insert_id;
			$data = '';
			$cart = $this->conn->query("SELECT c.*,p.name,i.price,p.id as pid, i.id as inventory_id from `cart` c inner join `inventory` i on i.id=c.inventory_id inner join products p on p.id = i.product_id where c.client_id ='{$client_id}' ");
			while ($row = $cart->fetch_assoc()):
				// Kiểm tra inventory_id tồn tại trong bảng inventory
				$check_inventory = $this->conn->query("SELECT id FROM `inventory` WHERE id = '{$row['inventory_id']}'");
				if ($check_inventory->num_rows == 0) {
					$resp['status'] = 'failed';
					$resp['error'] = "Inventory ID {$row['inventory_id']} không tồn tại.";
					return json_encode($resp);
				}

				if (!empty($data)) $data .= ", ";
				$total = $row['price'] * $row['quantity'];
				$data .= "('{$order_id}','{$row['inventory_id']}','{$row['quantity']}','{$row['price']}', $total)";
			endwhile;

			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total) VALUES {$data}";
			$save_olist = $this->conn->query($list_sql);
			if ($this->capture_err())
				return $this->capture_err();
			if ($save_olist) {
				$empty_cart = $this->conn->query("DELETE FROM `cart` where client_id = '{$client_id}'");
				$data = " order_id = '{$order_id}'";
				$data .= " ,total_amount = '{$amount}'";
				$save_sales = $this->conn->query("INSERT INTO `sales` set $data");
				if ($this->capture_err())
					return $this->capture_err();
				$resp['status'] = 'success';
				$this->settings->set_flashdata('success', " Đơn hàng đã được đặt thành công.");
			} else {
				$resp['status'] = 'failed';
				$resp['err_sql'] = $save_olist;
			}
		} else {
			$resp['status'] = 'failed';
			$resp['err_sql'] = $save_order;
		}
		return json_encode($resp);
	}
	function update_order_status()
	{
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `status` = '$status' where id = '{$id}' ");
		if ($update) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata("success", " Trạng thái đơn hàng được cập nhật thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function pay_order()
	{
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `paid` = '1' where id = '{$id}' ");
		if ($update) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata("success", " Trạng thái thanh toán đơn hàng được cập nhật thành công.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_account()
	{
		if (!empty($_POST['password'])) {
			$_POST['password'] = md5($_POST['password']); // Sử dụng đúng biến từ $_POST
		} else {
			unset($_POST['password']);
		}

		extract($_POST);
		$data = "";

		// Kiểm tra mật khẩu hiện tại
		if (md5($cpassword) != $this->settings->userdata('password')) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Mật khẩu hiện tại không chính xác";
			return json_encode($resp);
			exit;
		}

		// Kiểm tra email trùng lặp
		$check = $this->conn->query("SELECT * FROM `clients` where `email`='{$email}' and `id` != $id ")->num_rows;
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email đã được sử dụng.";
			return json_encode($resp);
			exit;
		}

		// Chuẩn bị dữ liệu để cập nhật
		foreach ($_POST as $k => $v) {
			if ($k == 'cpassword' || ($k == 'password' && empty($v)))
				continue;
			if (!empty($data)) $data .= ",";
			$data .= " `{$k}`='{$v}' ";
		}

		// Cập nhật thông tin tài khoản
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if ($save) {
			foreach ($_POST as $k => $v) {
				if ($k != 'cpassword')
					$this->settings->set_userdata($k, $v);
			}

			$this->settings->set_userdata('id', $this->conn->insert_id);
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', 'Tài khoản của bạn đã được cập nhật thành công.');
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}

		return json_encode($resp);
	}

	function update_client()
	{
		// Kiểm tra và mã hóa mật khẩu nếu có
		if (!empty($_POST['password'])) {
			$_POST['password'] = md5($_POST['password']);
		} else {
			unset($_POST['password']);
		}

		// Lấy các giá trị từ POST
		$email = $this->conn->real_escape_string($_POST['email']);
		$id = (int)$_POST['id'];  // Ép kiểu an toàn cho id

		// Kiểm tra email đã tồn tại hay chưa
		$check = $this->conn->query("SELECT * FROM `clients` WHERE `email`='{$email}' AND `id` != $id")->num_rows;
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email đã được sử dụng.";
			return json_encode($resp);
		}

		// Chuẩn bị dữ liệu cập nhật
		$data = "";
		foreach ($_POST as $k => $v) {
			if ($k == 'id')
				continue;
			if (!empty($data)) $data .= ",";
			$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
		}

		// Thực hiện câu lệnh cập nhật
		$save = $this->conn->query("UPDATE `clients` SET $data WHERE id = $id");
		if ($save) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', 'Thông tin khách hàng được cập nhật thành công.');
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}

		return json_encode($resp);
	}

	function delete_client()
	{
		extract($_POST);
		$delete = $this->conn->query("UPDATE `clients` set delete_flag = 1 where id = '{$id}'");
		if ($delete) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Khách hàng đã được xóa thành công");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_brand':
		echo $Master->save_brand();
		break;
	case 'delete_brand':
		echo $Master->delete_brand();
		break;
	case 'save_category':
		echo $Master->save_category();
		break;
	case 'delete_category':
		echo $Master->delete_category();
		break;
	case 'save_sub_category':
		echo $Master->save_sub_category();
		break;
	case 'delete_sub_category':
		echo $Master->delete_sub_category();
		break;
	case 'save_product':
		echo $Master->save_product();
		break;
	case 'delete_product':
		echo $Master->delete_product();
		break;

	case 'save_inventory':
		echo $Master->save_inventory();
		break;
	case 'delete_inventory':
		echo $Master->delete_inventory();
		break;
	case 'register':
		echo $Master->register();
		break;
	case 'add_to_cart':
		echo $Master->add_to_cart();
		break;
	case 'update_cart_qty':
		echo $Master->update_cart_qty();
		break;
	case 'delete_cart':
		echo $Master->delete_cart();
		break;
	case 'empty_cart':
		echo $Master->empty_cart();
		break;
	case 'delete_img':
		echo $Master->delete_img();
		break;
	case 'place_order':
		echo $Master->place_order();
		break;
	case 'update_order_status':
		echo $Master->update_order_status();
		break;
	case 'pay_order':
		echo $Master->pay_order();
		break;
	case 'update_account':
		echo $Master->update_account();
		break;
	case 'update_client':
		echo $Master->update_client();
		break;
	case 'delete_order':
		echo $Master->delete_order();
		break;
	case 'delete_client':
		echo $Master->delete_client();
		break;
	default:
		// echo $sysset->index();
		break;
}
