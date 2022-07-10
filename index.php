<?php

include('functions.php');

$ussdResponse = new stdclass;

$r = new Redis();
$r->connect('127.0.0.1', 6379);

$ussdRequest = json_decode(@file_get_contents('php://input'));
// checkreq($ussdRequest);

if ($ussdRequest != null) {
    $type = strtolower($ussdRequest->Type);
    // $session = $ussdRequest->Mobile;  //this is for creating a session with the mobile number, especially when you want to continue a session after a crash or a disconnection

    $session = $ussdRequest->SessionId; //this for creating the session with a session id. for an application like this you dont need to create a session with the mobile number.

    $auth = "1"; //authorized($ussdRequest->Mobile);
    if (empty($auth)) {
        $ussdResponse->Message =
            "Sorry, this number is not authorized";
        $ussdResponse->Type = "Release";
    } else {

        //$req = checkreq($ussdRequest);
        switch ($type) {
            case 'initiation':
            case 'Initiation':

                //commented section is for continining a session after a crash or a disconnection when the session is created with the mobile number.

                // if ($r->EXISTS($session)) {
                //     $r->hmset(
                //         $session,
                //         array(
                //             "session_id" => $session,
                //             "start_session" => "TRUE",
                //             "network" => strtoupper($ussdRequest->Operator)
                //         )
                //     );
                //     $ussdResponse->Message =
                //         "Biakoye revenue Collector" .
                //         "\nContinue from Previous Session?" .
                //         "\n1. Yes" .
                //         "\n0. No";
                //     $ussdResponse->Type = 'Response';
                // } else {

                $r->hmset($session, array(
                    "clientstate" => "useroption",
                    "session_id" => $session,
                    "start_session" => "FALSE",
                    "network" => strtoupper($ussdRequest->Operator)

                ));

                $ussdResponse->Message =
                    "Welcome to Biakoye revenue Collector" .
                    "\n1. Customer" .
                    "\n2. Agent";

                $ussdResponse->Type = 'Response';
                //}
                break;


            case 'response':
            case 'Response':
                $ClientState = $r->hget($session, "clientstate");
                $var = $ussdRequest->Message;
                if ($var == '0') {

                    $r->hmset($session, array("clientstate" => "useroption", "start_session" => "FALSE"));

                    $ussdResponse->Message =
                        "Welcome to Biakoye revenue Collector" .
                        "\n1. Customer" .
                        "\n2. Agent";

                    $ussdResponse->Type = 'Response';
                } else {

                    $continue = $r->hget($session, "start_session");
                    if ($continue == "TRUE") {
                        $ClientState = $r->hget($session, "current_state");
                        $r->hmset($session, array("start_session" => "FALSE"));
                    }
                    if ($ussdRequest->Message == "*") {
                        $ClientState = $r->hget($session, "backOption");
                    }

                    switch ($ClientState) {
                        case 'home':
                            if ($ussdRequest->Message == "*" || $continue == "TRUE") {
                                $option = $ussdRequest->Message;
                            }
                            if (!empty($option)) {
                                $r->hmset($session, array(
                                    "clientstate" => "useroption",
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));
                                $ussdResponse->Message =
                                    "Welcome to Biakoye revenue Collector" .
                                    "\n1. Customer" .
                                    "\n2. Agent";
                                $ussdResponse->Type = 'Response';
                            } else {

                                $ussdResponse->Message =
                                    "Invalid Input!" .
                                    "Welcome to Biakoye revenue Collector" .
                                    "\n1. Customer" .
                                    "\n2. Agent";
                                $ussdResponse->Type = 'Response';
                            }
                            break;


                        case 'useroption':
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;


                            if ($option == '1') {

                                $r->hmset($session, array(
                                    "clientstate" => "phone_check",
                                    "agent_code" => "00",
                                    "backOption" => "home",
                                    "customer_type_name" => "Individual",
                                    "business_type" => "direct_business",
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));

                                $ussdResponse->Message = "Please Enter your Phone Number \n(E.g. 0244123456)"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } elseif ($option == '2') {

                                $r->hmset($session, array(
                                    "clientstate" => "agent_check",
                                    // "business_type" => "2",
                                    "backOption" => "home",
                                    "customer_type_name" => "Intermediary",
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));

                                $ussdResponse->Message = "Please enter your agent code"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Wrong entry!\nPlease select an option" .
                                    "\n1. Customer" .
                                    "\n2. Agent";
                                $ussdResponse->Type = "Response";
                            }
                            break;

                        case 'agent_check':
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;


                            $agent_verified = check_agent($option);
                            if (!empty($agent_verified)) {
                                $r->hmset($session, array(
                                    "clientstate" => "phone_check",
                                    "agent_code" => $option,
                                    "backOption" => "useroption",
                                    "business_type" => "agent_business",
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));

                                $ussdResponse->Message = "Please Enter your Phone Number"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Invalid Agent Code\nPlease enter your agent code again"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            }
                            break;

                        case 'phone_check':

                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;

                            $backOption = ($r->hget($session, "business_type") == "agent_business") ? "agent_check" : "home";

                            $valid_contact = ValidateContact($option);
                            if (!empty($valid_contact)) {
                                $phone_details = checkPhone($valid_contact);
                                if (!empty($phone_details)) {

                                    $r->hmset($session, array(
                                        "clientstate" => "confirm_details",
                                        "agent_code" => "00",
                                        "backOption" => $backOption,
                                        "customer_phone" => $option,
                                        "customer_type_name" => "Individual",
                                        "customer_first_name" => $phone_details['first_name'],
                                        "customer_last_name" => $phone_details['last_name'],
                                        "customer_name" => $phone_details['first_name'] . " " . $phone_details['last_name'],
                                        "STRAIGHT" => "TRUE",  //to know whether to go back here or the last name 
                                        $ClientState => $option,
                                        "current_state" => $ClientState
                                    ));
                                    $ussdResponse->Message = "Please Select Revenue Type" .
                                        "\n1. Market Fees" .
                                        "\n2. Property Tax" .
                                        "\n3. Business Operation Fees"
                                        . "\n\n * Back\n0 Main Menu";
                                    $ussdResponse->Type = "Response";
                                } else {

                                    $r->hmset($session, array(
                                        "clientstate" => "last_name",
                                        "agent_code" => "00",
                                        "backOption" => "home",
                                        "customer_phone" => $option,
                                        "STRAIGHT" => "FALSE",  //to know whether to go back here or the last name
                                        $ClientState => $option,
                                        "current_state" => $ClientState
                                    ));

                                    $ussdResponse->Message = "Please Enter your First Name\n(E.g. John)"
                                        . "\n\n * Back\n0 Main Menu";
                                    $ussdResponse->Type = "Response";
                                }
                            } else {
                                $ussdResponse->Message = "Invalid Phone number!\nPlease Enter your Phone Number" .
                                    "\n(E.g. 0244123456)"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            }
                            break;


                        case 'last_name':
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;


                            if (!empty($option)) {
                                $r->hmset($session, array(
                                    "clientstate" => "rev_type",
                                    "backOption" => "phone_check",
                                    "customer_first_name" => $option,
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));
                                $ussdResponse->Message = "Please Enter your Last Name\n(E.g. Doe)"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Invalid Input!\nPlease Enter your First Name\n(E.g. Doe)"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            }
                            break;

                        case 'rev_type':
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;



                            if (!empty($option)) {
                                $r->hmset($session, array(
                                    "clientstate" => "confirm_details",
                                    "backOption" => "last_name",
                                    "customer_last_name" => $option,
                                    "customer_name" => $r->hget($session, "customer_first_name") . " " . $option,
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));
                                $ussdResponse->Message = "Please Select Revenue Type" .
                                    "\n1. Market Fees" .
                                    "\n2. Property Tax" .
                                    "\n3. Business Operation Fees"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                                break;
                            } else {
                                $ussdResponse->Message = "Invalid Input!\nPlease Enter your Last Name\n(E.g. Doe)"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            }
                            break;


                        case "confirm_details":
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;

                            $rev_type = check_revType($option);

                            $backOption = $r->hget($session, "STRAIGHT") == "TRUE" ? "phone_check" : "rev_type";

                            $client_state_Option = $r->hget($session, "business_type") == "direct_business" ? "topayment" : "paymentOption";

                            if (!empty($rev_type)) {
                                $r->hmset($session, array(
                                    "clientstate" => $client_state_Option,
                                    "backOption" => $backOption,
                                    "customer_revenue_type" => $option,
                                    "customer_revenue_type_name" => $rev_type,
                                    "amount" => "200.00",
                                    $ClientState => $option,
                                    "current_state" => $ClientState
                                ));

                                $ussdResponse->Message =
                                    "\nName: " . $r->hget($session, "customer_name")
                                    . "\nPhone: " . $r->hget($session, "customer_phone")
                                    . "\nRevenue Type: " . $rev_type //$r->hget($session, "premium")
                                    . "\nAmount: GHs 200" //. $r->hget($session, "premium")  //get the amount from API
                                    . "\n1 to confirm"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Invalid Input!\nPlease Select Revenue Type" .
                                    "\n1. Market Fees" .
                                    "\n2. Property Tax" .
                                    "\n3. Business Operation Fees"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            }
                            break;



                        case 'topayment':
                            if ($ussdRequest->Message == "*" || $continue == "TRUE") {

                                $option = $r->hget($session, $ClientState);
                                // //var_dump($option . " " . $ClientState);
                            } else {
                                $option = $ussdRequest->Message;
                            }

                            if ($option == "1") {
                                $r->hmset($session, array(
                                    "clientstate" => "Thankyou",
                                    // "order_id" => "MOMO-" . time(),
                                    $ClientState => $option,
                                    "current_state" => $ClientState,
                                    "backOption" => "confirm_details",

                                    //get details from redis cache

                                    "USSD_phone" =>  $ussdRequest->Mobile,

                                    "order_id" => $ussdRequest->SessionId,
                                    "network" => $ussdRequest->Operator,
                                ));
                                $ussdResponse->Message =
                                    "Please press 1 to proceed to payment"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Invalid"
                                    . "\nName: " . $r->hget($session, "customer_name")
                                    . "\nPhone: " . $r->hget($session, "customer_phone")
                                    . "\nRevenue Type: " . $r->hget($session, "customer_revenue_type_name")
                                    . "\nAmount: GHs " . $r->hget($session, "amount")  //get the amount from API
                                    . "\n1 to confirm"
                                    . "\n\n * Back\n0 Main Menu";

                                $ussdResponse->Type = "Response";
                            }

                            break;

                        case 'paymentOption':
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;


                            if ($option == "1") {
                                $r->hmset($session, array(
                                    "clientstate" => "payment",
                                    $ClientState => $option,
                                    "current_state" => $ClientState,
                                    "backOption" => "topayment",
                                    "payment_option" => "1"
                                ));

                                $ussdResponse->Message = "Please Select Payment Option" .
                                    "\n1. Cash" .
                                    "\n2. Mobile Money"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Invalid"
                                    . "\nName: " . $r->hget($session, "customer_name")
                                    . "\nPhone: " . $r->hget($session, "customer_phone")
                                    . "\nRevenue Type: " . $r->hget($session, "customer_revenue_type_name")
                                    . "\nAmount: GHs " . $r->hget($session, "amount")  //get the amount from API
                                    . "\n1 to confirm"
                                    . "\n\n * Back\n0 Main Menu";

                                $ussdResponse->Type = "Response";
                            }
                            break;

                        case 'payment':
                            $option = ($ussdRequest->Message == "*" || $continue == "TRUE") ? $r->hget($session, $ClientState) : $ussdRequest->Message;


                            if ($option == "1") {
                                $r->hmset($session, array(
                                    "clientstate" => "Thankyou",
                                    $ClientState => $option,
                                    "current_state" => $ClientState,
                                    "backOption" => "paymentOption",
                                    "payment_option" => "cash",
                                    //get details from redis cache

                                    "USSD_phone" =>  $ussdRequest->Mobile,

                                    "order_id" => $ussdRequest->SessionId,
                                    "network" => $ussdRequest->Operator,
                                ));
                                $ussdResponse->Message =
                                    "Dear Agent\nPlease press 1 to confirm receipt of cash amount of GHs 200"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } elseif ($option == '2') {

                                $r->hmset($session, array(
                                    "clientstate" => "Thankyou",
                                    $ClientState => $option,
                                    "current_state" => $ClientState,
                                    "backOption" => "confirm_details",
                                    "USSD_phone" =>  $ussdRequest->Mobile,
                                    "amount" => $r->hget($session, "customer_amount"),
                                    "order_id" => $ussdRequest->SessionId,
                                    "network" => $ussdRequest->Operator,
                                ));
                                $ussdResponse->Message =
                                    "Please press 1 to proceed to payment"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            } else {
                                $ussdResponse->Message = "Invalid"
                                    . "\nPlease Select Payment Option" .
                                    "\n1. Cash" .
                                    "\n2. Mobile Money"
                                    . "\n\n * Back\n0 Main Menu";
                                $ussdResponse->Type = "Response";
                            }
                            break;

                        case 'Thankyou':
                            $option = $ussdRequest->Message;

                            $data = $r->hGetAll($session);
                            // checkreq($data);

                            if ($option == "1") {
                                //checkreq($data);
                                $res = saveUssd($data);
                                $r->del($session);
                                $r->unlink($session);
                                //checkreq($data);
                                $ussdResponse->Message =
                                    "Thank you for paying your Tax\n You will receive a payment prompt shortly";
                                $ussdResponse->Type = "Release";
                            } else {
                                $ussdResponse->Message = "Invalid"
                                    . "\nPlease press 1 to proceed to payment";
                                $ussdResponse->Type = "Response";
                            }

                            break;

                            #endregion

                        default:
                            $ussdResponse->Message = "Invalid State";
                            $ussdResponse->Type = "Release";
                            break;
                    }
                }
                break;

            default:
                $ussdResponse->Message = "Invalid Request Type";
                $ussdResponse->Type = "Release";
                break;
        }
    }
} else {

    $ussdResponse->Message = 'Invalid USSD request,Biakoye Revenue Collector';
    $ussdResponse->Type = 'Release';
}
header('Content-type: application/json; charset=utf-8');
echo json_encode($ussdResponse);
