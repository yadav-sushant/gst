<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    $status = true; $code=200; $description = ''; $data = [];
    $header = apache_request_headers();

    // echo json_encode($header); exit;
    if($header['Authorization'] == 'Bearer 9921965797')
    {
        $input = json_decode(file_get_contents("php://input"));

        $gstin = $input->gstin; 
        if($gstin)
        {
            if(strlen($gstin)==15)
            {
                $fields = ['Trade Name','Legal Name', 'Registration Status', 'Registration Date', 'Registration Type', 'Return Periodicity', 'Entity Type', 'PAN', 'Pin Code', 'Place of Business'];
                
                $output = @file_get_contents('http://gst.jamku.app/gstin/'.$gstin);
                if($output)
                {
                    $output = strip_tags($output);
                    $start = strpos($output,'Profile')+8;
                    $output = substr($output, $start);
                    $end = strpos($output,'GST number');

                    $output = substr($output, 0, $end);
                    $output = str_replace(array("\n","\r\n","\r","                 "), '', $output);

                    // echo  $output.'<br><br>';

                    $count = count($fields);
                    for($i=0; $i<$count; $i++){
                        $output = str_replace($fields[$i],'</div><div class="key">'.str_replace(' ','_',strtolower($fields[$i])).'</div><div class="value">',$output);
                    }
                    $output .= '</div>';
                    
                    preg_match_all('#<div class="key">(.+?)</div>#', $output, $keys);
                    preg_match_all('#<div class="value">(.+?)</div>#', $output, $values);

                    $keys = $keys[1];
                    $values = array_map('trim', $values[1]);

                    if(count($keys) != count($values)){
                        $code = 500;
                        $status = false;
                        $description = 'Ooops! Something went wrong.';
                    }else{
                        $data = array_combine($keys, $values);
                        if(empty($data)){
                            $code = 404;
                            $status = false;
                            $description = 'Record not found.';
                        }
                    }
                }
                else{
                    $code = 404;
                    $status = false;
                    $description = 'Record not found.';
                }
            }
            else{
                $code = 403;
                $status = false;
                $description = 'GSTIN must be 15 digits.';
            }
        }
        else{
            $code = 403;
            $status = false;
            $description = 'The field GSTIN is required.';
        }
    }
    else{
        $code = 401;
        $status = false;
        $description = 'You are not authorized to access the link requested.';
    }

    http_response_code($code);     
    echo json_encode(["status"=>$status, "description"=>$description, "data"=>$data]); 
?>