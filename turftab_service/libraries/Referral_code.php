<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Referral_Code
{
    
    private $initial_code = 'TT-0000-AAA';

    // To create dynamic referral code with sequence
    public function generate_referral_code()
    {

        // Referral code file url
        $file_url = __DIR__ ."/../../assets/referral_code/code.txt";

        // Check the file exists or not
        if(!file_exists($file_url)) {

            // Create a file and put initial code
            $ref_action = file_put_contents($file_url,$this->initial_code);
        }

        // Retrieve the recent code from file
        $ref_str = file_get_contents($file_url);

        // Explode the value of recent referral code
        $split_value = explode('-', $ref_str);

        // Manage the integer value
        $generated_code = $this->create_dynamic_integer($split_value[1],$split_value[2]);

        $new_ref_code = 'TT-'.$generated_code;
        $ref_action = file_put_contents($file_url,$new_ref_code);
        $new_code = str_replace('-','',$new_ref_code);

        return $new_code;
    }

    // To create dynamic integer values
    public function create_dynamic_integer($inte,$chars)
    {

        if($inte > 9999)
        {
            $inte = '0000';
            $chars = $this->create_dynamic_chars($chars); 
        }
        else
        {
            $inte++;
        }

        $inte = sprintf('%04d', $inte);

        return $inte.'-'.$chars; 
    }

    // To create dynamic alphabets value
    public function create_dynamic_chars($chars)
    {

        $split_string = str_split($chars);

        if(ord($split_string[2]) < 95)
        {
            $split_string[2] =  chr(ord($split_string[2]) + 1);
        }
        else
        {
            $split_string[2] =  'A';    
        
            if(ord($split_string[1]) < 95)
            {
                $split_string[1] =  chr(ord($split_string[1]) + 1);
            }
            else
            {

                $split_string[1] =  'A';
                $split_string[0] =  chr(ord($split_string[0]) + 1);
                
                // Not needed for three chars
                // if(ord($split_string[0]) < 95)
                // {
                //     $split_string[0] =  chr(ord($split_string[0]) + 1);
                // }
                // else
                // {
                //     $split_string[0] =  'A';    
                // }
            }
        }

        return implode('',$split_string);
    }

}

/* End of file Referral_Code.php */