<?php

defined("BASEPATH") OR exit("No direct script access allowed");

$lang["internal_error"] = "Something went wrong. Please try again.";
//$lang["access_token_err"] = "Access token does not match.";
$lang["access_token_err"] = "Looks like you are signed in on a different device. Please log out to continue using this device.";
$lang["user_signup_success"] = "User registered successfully.";
$lang["user_signin_success"] = "Login Successfully.";
$lang["invalid_username"] = "Invalid username or password.";
$lang["email_not_send"] = "User registered successfully but failed to send mail.";
$lang["otp_not_updated"] = "OTP not updated.";
$lang["otp_verify_success"] = "OTP verified successfully.";
$lang["otp_verify_fail"] = "Please enter correct OTP.";
$lang["otp_send_fail"] = "Failed to send OTP.";
$lang["user_anauthorized"] = "User is not verified.";
$lang["record_found"] = "Record found.";
$lang["record_not_found"] = "Record not found.";
$lang["forgot_pwd_success"] = "Reset password link sent successfully on your registered email address.";
$lang["forgot_pwd_fail"] = "Failed to send reset password link.";
$lang["change_password_success"] = "Password updated successfully.";
$lang["change_password_fail"] = "Failed to update password.";
$lang["sign_out_success"] = "Successfully Signout.";
$lang["invalid_email"] = "Invalid email address.";
$lang["add_catch_log_success"] = "Catch Log added successfully.";
$lang["resend_otp_success"] = "New OTP sent successfully.";
$lang["profile_update_success"] = "Profile updated successfully.";
$lang["profile_update_fail"] = "Profile is not updated.";
$lang["vessel_add_success"] = "Vessel added successfully.";
$lang["vessel_add_fail"] = "Vessel added failed.";
$lang["vessel_not_exist"] = "Vessel is deleted or not exist.";

$lang["vessel_delete_success"] = "Vessel deleted successfully.";
$lang["vessel_delete_fail"] = "Vessel delete failed.";
$lang["fav_exporter_delete_success"] = "Favourite exporter deleted successfully.";
$lang["fav_exporter_delete_fail"] = "Favourite exporter delete failed.";
$lang["fav_exporter_add_success"] = "Favourite exporter added successfully.";
$lang["fav_exporter_add_fail"] = "Favourite exporter add failed.";

$lang["update_request_success"] = "Thank you for your request. MPEDA will be reverting to you at the earliest.";
$lang["add_remark_success"] = "Remark added successfully.";

$lang['voyage_ref_count_catch'] = 'Sorry, the same Vessel has another voyage in the system for the same dates or intersecting dates. You cannot add the same vessel with these dates';
$lang['voyage_ref_count_purchase'] = 'This voyage has already been added in the system by the Exporter to whom you have sold the catch. Kindly ensure with them.';
$lang['add_species_success'] = 'Fish specie added successfully.';
$lang['update_species_success'] = 'Fish specie quantity updated successfully.';
$lang['add_exporter_success'] = 'Exporter added successfully.';
$lang['update_exporter_success'] = 'Exporter quantity updated successfully.';
$lang['specie_already_exist'] = 'You have already added this Species in this Catch log. Kindly edit the same as required.';
$lang['invalid_species_qty'] = 'Sorry, you are not allowed to add less than %s quantity.';
$lang['invalid_specie'] = 'The specie you have entered is not exist.';
$lang['invalid_specie_exporter'] = 'The specie or exporter you have entered is not exist in catch log.';
$lang['purchase_qty_exceed'] = 'You cannot exceed the total balance quantity of this species in this catch log.';
$lang['invalid_vessel'] = 'Oops, this Vessel does not exist now. Kindly check with MPEDA for more information.';
?>