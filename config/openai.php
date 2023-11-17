<?php

/*chatgpt配置*/
return [
    'chatgpt'     => [ //请求参数
        'model'             => 'gpt-3.5-turbo', //模型
        "stream"            => true, //建议流输出，体验好
        'temperature'       => 1.0,
//            'max_tokens'        => 50,
        'frequency_penalty' => 1.0,
        'presence_penalty'  => 1.0,
    ],
    'api_key'     => '', //替换自己的apikey
    'base_url'    => 'https://openai.igptcat.xyz/openai/beta',//基础url如果海外服务器直接官方，如果国内服务器加海外云函数则自定义域名
    'filter_text' => [ //敏感词过滤数组,防止提问敏感问题导致ip被墙
        '测试一下',
    ]
];