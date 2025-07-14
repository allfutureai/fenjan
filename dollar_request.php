<?php

include("admin/connect.php");
include "admin/phpqrcode/qrlib.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formerror = [];
        $dollar_amount = $_POST['dollar_amount'];
        $port_type = $_POST['port_type'];
        $where_receieve_dollar = $_POST['where_receieve_dollar'];
        $dollar_phone     = $_POST['dollar_phone'];
        $dollar_name = $_POST['dollar_name'];
        $travel_date = $_POST['travel_date'];
        $travel_to = $_POST['travel_to'];
        $dollar_how_pay = $_POST['dollar_how_pay'];
        date_default_timezone_set('Asia/Baghdad');
        $date = date('Y-m-d H:i');
        ///////////////// Upload Id Number Images 

        ///////////Insert Passport Image /////////
        if (!empty($_FILES['passport_image']['name'])) {

            $passport_image_name = $_FILES['passport_image']['name'];
            $passport_image_name = str_replace(' ', '', $passport_image_name);
            $passport_image_temp = $_FILES['passport_image']['tmp_name'];
            $passport_image_type = $_FILES['passport_image']['type'];
            $passport_image_size = $_FILES['passport_image']['size'];
            $passport_image_uploaded = time() . '_' . $passport_image_name;
            move_uploaded_file($passport_image_temp, 'admin/dollar_orders/uploads/' . $passport_image_uploaded);
        } else {
            $formerror[] = ' من فضلك ادخل صورة جواز السفر ';
        }
        ///////////Insert Ticket Image  /////////
        if (!empty($_FILES['ticket_image']['name'])) {

            $ticket_image_name = $_FILES['ticket_image']['name'];
            $ticket_image_name = str_replace(' ', '', $ticket_image_name);
            $ticket_image_temp = $_FILES['ticket_image']['tmp_name'];
            $ticket_image_type = $_FILES['ticket_image']['type'];
            $ticket_image_size = $_FILES['ticket_image']['size'];
            $ticket_image_uploaded = time() . '_' . $ticket_image_name;
            move_uploaded_file($ticket_image_temp, 'admin/dollar_orders/uploads/' . $ticket_image_uploaded);
        } else {
            $formerror[] = ' من فضلك ادخل صورة تذكرة السفر  ';
        }
        // get the order number 
        $stmt = $connect->prepare("SELECT * FROM dollar ORDER BY id DESC");
        $stmt->execute();
        $last_order = $stmt->fetch();
        $count_orders = $stmt->rowCount();
        //$new_order_number = $last_order_number + 1;
        $new_order_number = rand(1000,9000 );
        if ($count_orders > 0) {
            $last_step_number = $last_order['step_number'];
            if ($last_step_number >= 100) {
                $new_step_number = 10;
            } else {
                $new_step_number = $last_step_number + 1;
            }
        }else{
            $new_step_number = 10;
        }
        $_SESSION['order_number'] = $new_order_number;
        $_SESSION['step_number'] = $new_step_number;
        if (empty($formerror)) {
            $stmt = $connect->prepare("INSERT INTO dollar (order_number,step_number,dollar_amount,port_type,where_receieve_dollar,
                        dollar_phone,dollar_name,travel_date,travel_to,dollar_how_pay,passport_image,ticket_image,id_image_first,person_image,created_at)
                        VALUES(:zorder_number,:zstep_number,:zdollar_amount,:zport_type,:zwhere_receieve_dollar,:zdollar_phone,:zdollar_name,
                        :ztravel_date,:ztravel_to,:zdollar_how_pay,:zpassport_image,:zticket_image,:zid_image_first,:zperson_image,:zcreated_at)
                        ");
            $stmt->execute(array(
                "zorder_number" => $new_order_number,
                "zstep_number" => $new_step_number,
                "zdollar_amount" => $dollar_amount,
                "zport_type" => $port_type,
                "zwhere_receieve_dollar" => $where_receieve_dollar,
                "zdollar_phone" => $dollar_phone,
                "zdollar_name" => $dollar_name,
                "ztravel_date" => $travel_date,
                "ztravel_to" => $travel_to,
                "zdollar_how_pay" => $dollar_how_pay,
                "zpassport_image" => $passport_image_uploaded,
                "zticket_image" => $ticket_image_uploaded,
                "zid_image_first" => null,
                "zperson_image" => null,
                "zcreated_at" => $date
            ));

            if ($stmt) {

                $qcodedata = "الاسم: $dollar_name\nرقم الطلب : $new_order_number\nالمبلغ : $dollar_amount\nتاريخ السفر :  $travel_date\nبلد الوجهة: $travel_to\nحالة الطلب :  في انتظار الدفع  \n";
                
                // مسار حفظ الصورة لرمز الاستجابة السريعة
                $newPath = 'admin/uploads/qr_codes/';

                // اسم الملف لرمز الاستجابة السريعة
                $fileName = uniqid() . $dollar_name . ".png";

                // الجمع بين المسار واسم الملف للحصول على المسار الكامل للصورة
                $fullFilePath = $newPath . $fileName;

                // تعيين إعدادات QR
                QRcode::png($qcodedata, $fullFilePath, QR_ECLEVEL_H, 4);

?>
                <script>
                // تمرير المتغيرات من PHP إلى JavaScript
                window.location.href =
                    'dollar_confirm.html?booking_id=<?php echo urlencode($new_order_number); ?>&name=<?php echo urlencode($dollar_name); ?>&travel_date=<?php echo urlencode($travel_date); ?>&qr_code=<?php echo urlencode($fullFilePath); ?>';
            </script> 
            <?php
            }

        } else {
            $formerror_json = urlencode(json_encode($formerror));
          // إعادة التوجيه إلى western.html مع تمرير الأخطاء
  ?>
  <script>
      var errors = "<?php echo $formerror_json; ?>";
      window.location.href = 'dollar2.html?errors=' + errors;
  </script>
  <?php
  exit;
      }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}