## 提交表单， wordpress后台需要有接收提交表单数据的api, 这里使用了 contact form 7 插件，然后写一个简单的接口接收数据。 

```
https://XXX.com/wp-json/cf7-api/submit
{
  "_wpcf7": 111, // 表单ID
  "your-name": "John",
  "your-email": "john@example.com",
  "your-subject":"test"
}
```



add_action('rest_api_init', function () {
    register_rest_route('cf7-api', '/submit', [
        'methods' => 'POST',
        'callback' => 'handle_cf7_submission',
        // 添加权限验证（根据需求调整）
        'permission_callback' => '__return_true' // 或自定义函数如：'is_user_logged_in'
    ]);
});

function handle_cf7_submission(WP_REST_Request $request) {
    // 1. 获取表单ID和字段数据
    $_POST = $request->get_params(); // 覆盖全局变量

    // 2. 获取表单ID
    $form_id = isset($_POST['_wpcf7']) ? (int)$_POST['_wpcf7'] : 0;
    
    // 2. 验证表单ID有效性
    if (!$form_id || !($contact_form = WPCF7_ContactForm::get_instance($form_id))) {
        return new WP_REST_Response(['error' => 'Invalid form ID'], 400);
    }

    // 3. 执行CF7标准提交逻辑
    $submission = WPCF7_Submission::get_instance($contact_form);
    $result = $submission->get_result(); // 获取结果对象
    
    // 4. 返回结构化响应
    return new WP_REST_Response([
        'status' => $result['status'],
        'message' => $result['message'],
        'invalid_fields' => $result['invalid_fields'] ?? []
    ], 200);
}

