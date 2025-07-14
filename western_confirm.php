<?php
include("admin/connect.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
   // echo $booking_id;
    $formerror = [];
    ///////////Insert Confirm Order  /////////
    if (!empty($_FILES['image_confirm_order']['name'])) {
        // // الحصول على الاسم الأصلي للملف
        $image_confirm_order_name = $_FILES['image_confirm_order']['name'];
        $image_confirm_order_name = str_replace(' ', '', $image_confirm_order_name);
        $image_confirm_order_temp = $_FILES['image_confirm_order']['tmp_name'];
        $image_confirm_order_type = $_FILES['image_confirm_order']['type'];
        $image_confirm_order_size = $_FILES['image_confirm_order']['size'];
        $image_confirm_order_uploaded = time() . '_' . $image_confirm_order_name;
        move_uploaded_file($image_confirm_order_temp, 'admin/wester_orders/uploads/' . $image_confirm_order_uploaded);
    } else {
        $formerror[] = '  من فضلك ادخل صورة تاكيد الدفعد ';
    }
    if (empty($formerror)) {
        try{
            $stmt = $connect->prepare("SELECT * FROM western WHERE order_number = ?");
            $stmt->execute(array($booking_id));
            $order = $stmt->fetch();

            $stmt = $connect->prepare("UPDATE western SET pay_image = ? WHERE order_number =  ?");
            $stmt->execute(array($image_confirm_order_uploaded, $booking_id));
            if ($stmt) {
                if($order['transfer_method'] == 'ويسترن يونيون'){
                    ?>
                    <script>
        // تمرير المتغيرات من PHP إلى JavaScript
        window.location.href = 'westernunion.html';
    </script>
                    <?php 
    
                }elseif($order['transfer_method'] == 'موني جرام'){
                    ?>
                    <script>
        // تمرير المتغيرات من PHP إلى JavaScript
        window.location.href = 'money-grame.html';
    </script>
                    <?php 
                    
                }elseif($order['transfer_method'] == 'حساب بنكي'){
                    ?>
                    <script>
        // تمرير المتغيرات من PHP إلى JavaScript
        window.location.href = 'bank-transfer.html';
    </script>
                    <?php 
    
                }elseif($order['transfer_method'] == 'داخل المحافظات'){
                    ?>
    <script>
        // تمرير المتغيرات من PHP إلى JavaScript
        window.location.href = 'inside-transfer.html';
    </script>
                    <?php 
    
                }else{
    ?>
    <script>
        // تمرير المتغيرات من PHP إلى JavaScript
        window.location.href = 'money-transfer.html';
    </script>
    <?php 
                }
    
                
                            ?>
     
    <?php
    exit; 
            }
        }catch(\Exception $e){
            echo $e->getMessage();
        }
        
    } else {
        
          
                  $formerror_json = urlencode(json_encode($formerror));
            // إعادة التوجيه إلى western.html مع تمرير الأخطاء
    ?>
    <script>
        var errors = "<?php echo $formerror_json; ?>";
        window.location.href = 'https://samabaghdad-iq.vercel.app/money-transfer.html?errors=' + errors;
    </script>
    <?php
    exit;
    
//         foreach ($formerror as $error) {
//         ?>
//             <div class="alert alert-danger"> <?php echo $error; ?> </div>
// <?php
//         }
    }
}
