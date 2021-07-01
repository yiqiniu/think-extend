# yiqiniu-extend for ThinkPHP6

## 安装

> composer require yiqiniu/think-extend

## 配置

> 配置文件位于 `config/yqnapi.php`

### 公共配置

```
 [
    //不需要认证的操作
    'no_auth' => [
        'module' => [

        ],
        // 不需要认证的操作
        'action' => [
            'users/login'
        ],
        // 不需要认证的控制器
        'controller' => [],

    ],
    // APP 类型
    'app' => [
        0 => 'android',
        1 => 'ios',
        2 => 'weixin',
        3 => 'pc',
        9 => '其它'
    ],

    'auth' => [
        'token_key' => '',
        //过期时间:0不过期,时间单位秒
        'expire' => 0,
    ],
]
```

## 导出文件

### 导出execl

导入execl扩展包

```
composer require phpoffice/phpspreadsheet
```

example

```
    $titles = ['用户名', '电话'];
    $data = [
        ['张三', '1201221313132'],
        ['张三', '1201221313132'],
        ['张三', '1201221313132'],
    ];
    Extend::ExportFile()->exportExcel("test.xls", $titles, $data);

```

2. 调用函数

### 导出PDF

导入包

```
composer require mpdf/mpdf
```

指定汉字字体

```
          $params=[
            'name' => 'pingfang',
            'path'=>  public_path() . 'pdf/fonts',
            'data'=>[
                'pingfang' => [
                    'R' => 'PingFangMedium.ttf',
                    'I' => 'PingFangRegular.ttf',
                ]
            ]
        ];
        
    
```

example

```
        $params=[
            'path' => public_path() . 'pdf/fonts',
            'data' => [
                'pingfang' => [
                    'R' => 'PingFangMedium.ttf',
                    'I' => 'PingFangRegular.ttf',
                ]
            ],
            'name' => 'pingfang'
        ];
        $content = file_get_contents(public_path() . 'pdf/template.html');

        Extend::ExportFile()->exportPdf("aaa.pdf", $content,$params);
        
```



