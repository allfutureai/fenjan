<?php
    include("admin/connect.php");
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $booking_id = $_POST['booking_id'] ?? '';

        $image_confirm_order_name = $_FILES['image_confirm_order']['name'];
        $image_confirm_order_name = str_replace(' ', '', $image_confirm_order_name);
        $image_confirm_order_temp = $_FILES['image_confirm_order']['tmp_name'];
        $image_confirm_order_type = $_FILES['image_confirm_order']['type'];
        $image_confirm_order_size = $_FILES['image_confirm_order']['size'];
        $image_confirm_order_uploaded = time() . '_' . $image_confirm_order_name;
        move_uploaded_file($image_confirm_order_temp, 'admin/dollar_orders/uploads/' . $image_confirm_order_uploaded);

        $stmt = $connect->prepare("UPDATE dollar SET image_confirm_order = ? WHERE order_number =  ?");
        $stmt->execute(array($image_confirm_order_uploaded, $booking_id));
        if ($stmt) {
          //  echo "Gooood";
            ?>
<script>
    // تمرير المتغيرات من PHP إلى JavaScript
   window.location.href = 'dollar.html';
</script>
<?php
exit; 
        } else {
            echo "Errrrrrror";
             $formerror[] = '  من فضلك ادخل صورة تاكيد الدفع  ';
              $formerror_json = urlencode(json_encode($formerror));
            // إعادة التوجيه إلى western.html مع تمرير الأخطاء
    ?>
    <script>
        // var errors = "<?php echo $formerror_json; ?>";
     window.location.href = 'dollar.html?errors=' + errors;
    </script>
    <?php
    exit;
    
    
           
        }
    }

    ?>