##  以下函数作为为：接收前端chat请求，wordpress调用api回答问题， 可直接放在模板的function.php就可以。
##  frontend里面的文件，则自行放在模板的前端。例如:   footer里面。
##  https://wordpress.org/plugins/insert-headers-and-footers/  可以使用这个插件导入前端和php函数
##  配置好修改 api_url 为 自己的部署 python_backend的 api





// 在请求前增加 PHP 执行时间限制
set_time_limit(300); // 设置为 300 秒
// 修改默认套接字超时
ini_set('default_socket_timeout', 300);
//调整 wp-includes/http.php 超时限制值以解决服务器响应缓慢的问题
add_filter( 'http_request_args', 'bal_http_request_args', 1000, 1 );
function bal_http_request_args( $r ) //called on line 237
{
    $r['timeout'] = 25; //单位：秒
    return $r;
}
add_action( 'http_api_curl', 'bal_http_api_curl', 1000, 1 );
function bal_http_api_curl( $handle ) //called on line 1315
{
    curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 25 );
    curl_setopt( $handle, CURLOPT_TIMEOUT, 25 );
} 

function generate_chat_response( $last_prompt, $conversation_history ) {

// OpenAI API URL and key
$api_url = 'http://127.0.0.1:8000/chat';
$api_key = 'sk-0E'; // Replace with your actual API key

// Headers for the OpenAI API
$headers = [
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $api_key
];

// Add the last prompt to the conversation history
$conversation_history[] = [
    'role' => 'system',
    'content' => 'Answer questions only related to digital marketing, otherwise, say I dont know'
];

$conversation_history[] = [
    'role' => 'user',
    'content' => $last_prompt
];

// Body for the OpenAI API
$body = [
    'model' => 'gpt-4o-mini', // You can change the model if needed
    'messages0' => $conversation_history,
    'temperature' => 0.7, // You can adjust this value based on desired creativity
	'conversation_id'=> "beefb91df1fe41f99bd3a9c242f64ff8",
	'message'=>$last_prompt
];

// Args for the WordPress HTTP API
$args = [
    'method' => 'POST',
    'headers' => $headers,
    'body' => json_encode($body),
    'timeout' => 120,
		'connect_timeout' => 30, // 单独设置连接阶段的超时（秒）
	  'cookies'     => array(),  // Cookies
    'sslverify'   => false,    // 禁用SSL验证（测试环境）
    'connecttimeout' => 30,    // 连接超时时间（秒）
];

// Send the request
$response = wp_remote_request($api_url, $args);

// Handle the response
if (is_wp_error($response)) {
    return $response->get_error_message();
} else {
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid JSON in API response',
            'result' => ''
        ];
    } elseif (!isset($data['messages'])) {
        return [
            'success' => false,
            'message' => 'API request failed. Response: ' . $response_body,
            'result' => ''
        ];
    } else {
        $content = $data['messages'][0]['content'];
        return [
            'success' => true,
            'message' => 'Response Generated',
            'result' => $content
        ];
    }
}
}

function generate_dummy_response( $last_prompt, $conversation_history ) {
// Dummy static response
$dummy_response = array(
    'success' => true,
    'message' => 'done',
    'result' => "here is my reply"
);

// Return the dummy response as an associative array
return $dummy_response;
}

function handle_chat_bot_request( WP_REST_Request $request ) {
$last_prompt = $request->get_param('last_prompt');
$conversation_history = $request->get_param('conversation_history');

$response = generate_chat_response($last_prompt, $conversation_history);
return $response;
}

function load_chat_bot_base_configuration(WP_REST_Request $request) {
// You can retrieve user data or other dynamic information here
$user_avatar_url = "https://liangdabiao.com/wp-content/uploads/2025/06/a8a894b8b3e9259bb02ab0a7832372bb.png"; // Implement this function
$bot_image_url = "https://liangdabiao.com/wp-content/uploads/2025/06/c892d890243984bb66f17f138412d5f4.png"; // Implement this function

$response = array(
'botStatus' => 0,
'StartUpMessage' => "Hi, How are you?",
'fontSize' => '16',
'userAvatarURL' => $user_avatar_url,
'botImageURL' => $bot_image_url,
// Adding the new field
'commonButtons' => array(
    array(
        'buttonText' => 'I want your help!!!',
        'buttonPrompt' => 'I have a question about your courses'
    ),
    array(
        'buttonText' => 'I want a Discount',
        'buttonPrompt' => 'I want a discount'
    )

)

);

$response = new WP_REST_Response($response, 200);

return $response;
}

add_action( 'rest_api_init', function () {
register_rest_route( 'myapi/v1', '/chat-bot/', array(
   'methods' => 'POST',
   'callback' => 'handle_chat_bot_request',
   'permission_callback' => '__return_true'
) );

register_rest_route('myapi/v1', '/chat-bot-config', array(
    'methods' => 'GET',
    'callback' => 'load_chat_bot_base_configuration',
));
} );
