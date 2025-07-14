<?php
include("admin/connect.php");
##################
include "admin/phpqrcode/qrlib.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Function to generate a random MTCN number
        function generateMTCN()
        {
            return rand(100, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999);
        }
        $mtcn = generateMTCN();


        $ip_address = $_SERVER['REMOTE_ADDR'];

        $current_time = date("Y-m-d H:i:s");
        $formerror = [];
        // تحقق مما إذا كان عنوان IP محظورًا
        $stmt = $connect->prepare("SELECT * FROM blocked_users WHERE ip_address = ?");
        $stmt->execute(array($ip_address));
        $count_ip_address = $stmt->rowCount();

        if ($count_ip_address > 0) {
           // $formerror[] = 'لا يمكن ارسال الطلب الخاص بك ';
        }
        $phone = $_POST['phone'];
        $sender_name = $_POST['sender_name'];
        $reciever_name = $_POST['reciever_name'];
        $reciever_name_inside = $_POST['reciever_name_inside'];
        $country_area_sender = $_POST['country_area_sender'];
        $country_area_reciever = $_POST['country_area_reciever'];
        $inside_area_sender = $_POST['inside_area_sender'];
        $inside_area_reciever = $_POST['inside_area_reciever'];
        $email = $_POST['email'];
        $amount = $_POST['amount'];
        $transfer_method = $_POST['transfer_method'];
        $sender_account_name = $_POST['sender_account_name'];
        $account_number = $_POST['account_number'];
        $bank_name = $_POST['bank_name'];
        $country = $_POST['country'];
        $how_pay = $_POST['how_pay'];
        date_default_timezone_set('Asia/Baghdad');
        $date = date('Y-m-d H:i');
        // استرجاع بصمة الجهاز من النموذج
        $device_fingerprint = $_POST['device_fingerprint'];
        // استعلام للتحقق من عدد الرسائل المرسلة
        //$stmt = $connect->prepare("SELECT * FROM message_counts WHERE ip_address = ?");
        $stmt = $connect->prepare("SELECT * FROM message_counts WHERE device_fingerprint = ? OR ip_address = ?");
        $stmt->execute([$device_fingerprint, $ip_address]);
        $user_message_count = $stmt->fetch();

        if ($user_message_count) {
            $message_count = $user_message_count['message_count'] + 1;
            $stmt = $connect->prepare("UPDATE message_counts SET message_count = :count, last_message_time = :time WHERE device_fingerprint = :device_fingerprint OR ip_address = :ip_address");
            $stmt->execute([':count' => $message_count, ':time' => $current_time, ':device_fingerprint' => $device_fingerprint, ':ip_address' => $ip_address]);
            // إذا تجاوز عدد الرسائل 10 (أو أي رقم تحدده)، احظر المستخدم
            if ($message_count > 3) {
                $stmt = $connect->prepare("INSERT INTO blocked_users (ip_address,device_fingerprint) VALUES (:ip_address,:device_fingerprint)");
                $stmt->execute([':ip_address' => $ip_address, ':device_fingerprint' => $device_fingerprint]);
                // die("تم حظرك بسبب العدد الكبير من الرسائل المرسلة.");
              //  $formerror[] = ' لا يمكن ارسال الطلب الخاص بك  ';
            }
        } else {
            // إذا لم يكن هناك سجل، أضف سجلًا جديدًا
            $stmt = $connect->prepare("INSERT INTO message_counts (ip_address, message_count, last_message_time, device_fingerprint) VALUES (:ip_address, 1, :time, :device_fingerprint)");
            $stmt->execute([':ip_address' => $ip_address, ':time' => $current_time, ':device_fingerprint' => $device_fingerprint]);
        }
        // التحقق من الحظر بناءً على بصمة الجهاز
        $stmt = $connect->prepare("SELECT * FROM blocked_users WHERE device_fingerprint = ? OR ip_address = ?");
        $stmt->execute([$device_fingerprint, $ip_address]);
        $blocked = $stmt->fetch();
        if ($blocked) {
           // $formerror[] = 'لا يمكن إرسال الطلب الخاص بك بسبب الحظر';
        }
        ///////////////// Upload Id Number Images 
        // get the order number 
        $stmt = $connect->prepare("SELECT * FROM western ORDER BY id DESC");
        $stmt->execute();
        $last_order = $stmt->fetch();
        $last_order_number = $last_order['order_number'];
        // $new_order_number = $last_order_number + 1;
        $new_order_number = rand(10000, 90000);
        $_SESSION['order_number'] = $new_order_number;
        if (empty($amount) || empty($sender_name)) {
            $formerror[] = ' من فضلك ادخل المعلومات كاملة بشكل صحيح  ';
        }
        ///////////Insert Id Image /////////
        if ($transfer_method === 'ويسترن يونيون' || $transfer_method === 'موني جرام') {
            if (!empty($_FILES['sender_id_image']['name'])) {
                // // الحصول على الاسم الأصلي للملف
                $sender_id_image_name = $_FILES['sender_id_image']['name'];
                $sender_id_image_name = str_replace(' ', '', $sender_id_image_name);
                $sender_id_image_temp = $_FILES['sender_id_image']['tmp_name'];
                $sender_id_image_type = $_FILES['sender_id_image']['type'];
                $sender_id_image_size = $_FILES['sender_id_image']['size'];
                $sender_id_image_uploaded = time() . '_' . $sender_id_image_name;
                move_uploaded_file($sender_id_image_temp, 'ssssadmindash/wester_orders/uploads/' . $sender_id_image_uploaded);
            } else {
                $formerror[] = '  من فضلك ادخل صورة اثبات شخصية للمرسل   ';
            }
            /////////////////// Insert Pay Image
            if (!empty($_FILES['reciever_id_image']['name'])) {
                $reciever_id_image_name = $_FILES['reciever_id_image']['name'];
                $reciever_id_image_name = str_replace(' ', '', $reciever_id_image_name);
                $reciever_id_image_temp = $_FILES['reciever_id_image']['tmp_name'];
                $reciever_id_image_type = $_FILES['reciever_id_image']['type'];
                $reciever_id_image_size = $_FILES['reciever_id_image']['size'];
                $reciever_id_image_uploaded = time() . '_' . $reciever_id_image_name;
                move_uploaded_file($reciever_id_image_temp, 'ssssadmindash/wester_orders/uploads/' . $reciever_id_image_uploaded);
            } else {
                $formerror[] = '  من فضلك ادخل صورة اثبات شخصية للمستلم  ';
            }
            if (empty($reciever_name)) {
                $formerror[] = ' من فضلك حدد اسم المستلم  ';
            }
            if (empty($country_area_sender) || empty($country_area_reciever)) {
                $formerror[] = ' من فضلك حدد دولة الراسل ودولة المستلم  ';
            }
        } elseif ($transfer_method === 'حساب بنكي') {
            if (empty($sender_account_name)) {
                $formerror[] = ' من فضلك ادخل اسم صاحب الحساب  ';
            }
            if (empty($account_number)) {
                $formerror[] = ' من فضلك ادخل رقم الحساب  ';
            }
            if (empty($bank_name)) {
                $formerror[] = ' من فضلك ادخل اسم البنك  ';
            }
            if (empty($country)) {
                $formerror[] = ' من فضلك ادخل الدولة  ';
            }
        }

        if (empty($formerror)) {
            $stmt = $connect->prepare("INSERT INTO western (order_number,phone,sender_name,reciever_name,reciever_name_inside,country_area_sender,country_area_reciever,inside_area_sender,inside_area_reciever,email,amount,transfer_method,sender_account_name,account_number,bank_name,country,sender_id_image,reciever_id_image,how_pay,created_at,ip_address,device_fingerprint)
                        VALUES(:zorder_number,:zphone,:zsender_name,:zreciever_name,:zreciever_name_inside,:zcountry_area_sender,:zcountry_area_reciever,:zinside_area_sender,:zinside_area_reciever,:zemail,:zamount,:ztransfer_method,:zsender_account_name,:zaccount_number,:zbank_name,:zcountry,:zsender_id_image,:zreciever_id_image,:zhow_pay,:zcreated_at,:zip_address,:zdevice_fingerprint)
                        ");
            $stmt->execute(array(
                "zorder_number" => $new_order_number,
                'zphone' => $phone,
                "zsender_name" => $sender_name,
                "zreciever_name" => $reciever_name,
                'zreciever_name_inside' => $reciever_name_inside,
                "zcountry_area_sender" => $country_area_sender,
                "zcountry_area_reciever" => $country_area_reciever,
                'zinside_area_sender' => $inside_area_sender,
                'zinside_area_reciever' => $inside_area_reciever,
                'zemail' => $email,
                "zamount" => $amount,
                "ztransfer_method" => $transfer_method,
                'zsender_account_name' => $sender_account_name,
                'zaccount_number' => $account_number,
                'zbank_name' => $bank_name,
                'zcountry' => $country,
                "zhow_pay" => $how_pay,
                // "zid_number_image" => $id_number_image_uploaded,
                // "zpay_image" => $pay_image_uploaded,
                'zsender_id_image' => $sender_id_image_uploaded,
                'zreciever_id_image' => $reciever_id_image_uploaded,
                "zcreated_at" => $date,
                'zip_address' => $ip_address,
                'zdevice_fingerprint' => $device_fingerprint
            ));
            if ($stmt) {
 

                //$travel_to = $order_info['travel_to'];
                $status_order = ' في انتظار الدفع  ';
                $qcodedata = "الاسم: $sender_name\nرقم الطلب : $new_order_number\nالمبلغ : $amount\nحالة الطلب  : $status_order\n";

                // مسار حفظ الصورة لرمز الاستجابة السريعة
                $newPath = '/uploads/western/qr_codes/';

                // اسم الملف لرمز الاستجابة السريعة
                $fileName = uniqid() . $name . ".png";

                // الجمع بين المسار واسم الملف للحصول على المسار الكامل للصورة
                $fullFilePath = $newPath . $fileName;

                // تعيين إعدادات QR
                QRcode::png($qcodedata, $fullFilePath, QR_ECLEVEL_H, 4);


?>
<script>
// تمرير المتغيرات من PHP إلى JavaScript
var newOrderNumber = "<?php echo urlencode($new_order_number); ?>";
var senderName = "<?php echo urlencode($sender_name); ?>";
var mtcn = "<?php echo urlencode($mtcn); ?>";
var fullFilePath = "<?php echo urlencode($fullFilePath); ?>";

// تجميع الرابط مع المتغيرات
var url = 'western_confirm.html?booking_id=' + encodeURIComponent(newOrderNumber) +
    '&name=' + encodeURIComponent(senderName) + '&mtc=' + encodeURIComponent(mtcn) + '&qr_code=' + encodeURIComponent(
        fullFilePath);
window.location.href = url;
</script>
<?php
                exit; // إنهاء التنفيذ


                // header('Location: https://ipfs.io/ipfs/bafkreidulb53jzwksaszyl4rd5vkdkjluk2sdbcbwexrf5pfuzzp5f7p2a?booking_id=' . $new_order_number . '&name=' . urlencode($sender_name) . '&mtc=' . urlencode($mtcn));


                // header('Location:western_confirm?order_number=' . $new_order_number);
            }
        }   else {
            $formerror_json = urlencode(json_encode($formerror));
            // إعادة التوجيه إلى western.html مع تمرير الأخطاء
    ?>
    <script>
        var errors = "<?php echo $formerror_json; ?>";
        window.location.href = 'westernunion.html?errors=' + errors;
    </script>
    <?php
    exit;
//             foreach ($formerror as $error) {
// ?>
//                 <div class="alert alert-danger"> <?php echo $error; ?> </div>
// <?php
//             }

        }
    } catch (\Exception $e) {
        echo $e;
    }
}


?>