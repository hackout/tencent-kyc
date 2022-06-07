# 腾讯云 人脸核身(微信H5)

一个页面解决人脸核身问题，参考[TencentCloudSDK](https://github.com/TencentCloud/tencentcloud-sdk-php)

## 使用示例
```
require_once('TencentKYC.php');

$kyc = new TencentKYC('1','您的app','密钥');

$result = $kyc->openKYC("真实姓名",'证件号码');

```