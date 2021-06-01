<?php
/**
 * SMAPI by MrJun
 */
class SMApi
{
	private $auth;
    private $name;
    private $pswd;
    private $source_type;

	public function __construct($auth,$name,$pswd,$source_type)
	{
		$this->auth = $auth;
		$this->name = $name;
		$this->pswd = $pswd;
		$this->source_type = $source_type;
	}

	public function Upload($path)
	{
        if ($this->source_type == 1){ // sm
            $smfile = new \CURLFile(realpath($path));
            $post_data = [
                "smfile" => $smfile,
                "format" => 'json'
            ];
        }else{ // hello
            $smfile = new \CURLFile(realpath($path));
            $post_data = [
                "source" => $smfile,
                "login-subject" => $this->name,
                "password" => $this->pswd,
                "format" => 'json'
            ];
        }


		$result = $this->Send('upload', $post_data, 1);

		return $result;
	}

	public function Delete($hash)
	{
		$result = $this->Send('delete/'.$hash);

		return $result;
	}

	public function Send($type, $data = [], $is_post = 0)
	{
		$user_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36";
        if ($this->source_type == 1) { // sm
            $url = 'https://sm.ms/api/v2/'.$type;
            $headers = array(
                "Content-type: multipart/form-data",
                "Authorization: ".$this->auth
            );
        }else{ // hello
            $url = 'https://www.helloimg.com/newapi/2/upload';
            $headers = array(
                "Content-type: multipart/form-data",
                "login-subject" => $this->name,
                "password" => $this->pswd,
            );
        }

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($is_post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$output = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
		$output = json_decode($output, true);
		return $output;
	}
}
