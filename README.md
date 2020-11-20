# yiqiniu-extend for ThinkPHP6

## 安装

> composer require yiqiniu/think-extend

## 配置

> 配置文件位于 `config/extend.php`

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
## 使用说明

### jwt 认证
```
composer require firebase/php-jwt
```



###  命令说明
扩展命令
```
  yqn:facade        Create a new Facade class
  yqn:loader        Generate Specifies the folder proxy class
  yqn:model         Generate all models from the database
  yqn:server        Create a new Socket Server command class
  yqn:uuid          Create a new uuid value
  yqn:validate      Generate all validations based on database table fields

```

生成加载类yqn:facade
```
//查看帮助
php think yqn:facade 

Usage:
  make:facade [options] [--] <name>

Arguments:
  name                  The name of the class

Options:
  -m, --module=MODULE   指定输出的模块
  -d, --dir             是否为目录,目录时批量生成
  -p, --parent=PARENT   读取父类注释与生成的合并
  -f, --framework       读取框架的类的注释

php think yqn:loader index/model -m index -s Model
```

给目录中的所有类生成加载类
```
yqn:loader        Generate Specifies the folder proxy class

php think yqn:loader  --help
Usage:
  yqn:loader_class [options] [--] [<dir>]

Arguments:
  dir                   Specified Target Directory

Options:
  -p, --prefix=PREFIX   Class Prefix  类前缀
  -s, --suffix=SUFFIX   Class Suffix  类后缀
  -m, --module=MODULE   specified Module name       保存到的模块
  -h, --help            Display this help message
  -V, --version         Display this console version
  -q, --quiet           Do not output any message


例：
    php think yqn:loader index/model -m index -s Model
    给index/model 中的类生成 加载类   后缀:Model
```

根据表生成Model类

```

php think yqn:model  --help
Usage:
  make:modelall [options]

Options:
  -f, --force                      force update
  -s, --schema=SCHEMA              specified schema name
  -m, --module=MODULE              specified Module name
  -k, --keyword=KEYWORD            specified table name keyword
  -d, --subdirectory=SUBDIRECTORY  specified SubDirectories

例：
   php think yqn:model -k product -d product -m index
   生成 表名中包含 product 的表  生成到model 目录下的product 子目录下  模块index
```

根据表生成验证类
```
php think yqn:validate --help
Usage:
  make:validateall [options]

Options:
  -a, --all                        Make All Fields
  -f, --force                      force update
  -s, --schema=SCHEMA              specified schema name
  -m, --module=MODULE              specified Module name
  -t, --table=TABLE                specified table name
  -k, --keyword=KEYWORD            specified table name keyword
  -d, --subdirectory=SUBDIRECTORY  specified SubDirectories

例：
php think yqn:validate -k product -d product

```

生成uuid, 不重复的字符串

```

php think yqn:uuid
new uuid string:1d3bf180116d85ff4ba6a02c1d588a24

```

