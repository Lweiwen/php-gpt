<?php

/*chatgpt配置*/
return [
    'channel'     => 1,
    'openai'      => [
        'model'             => 'gpt-3.5-turbo', //模型
        "stream"            => true, //建议流输出，体验好
        'temperature'       => 1.0,
        'max_tokens'        => 0,
        'frequency_penalty' => 1.0,
        'presence_penalty'  => 1.0,
        'api_key'           => 'sk-svip8fTb6OKHQTaTovwxT2222223BlbkFJSyVsywKhlQvWQs97OBsv', //替换自己的apikey
        'base_url'          => 'https://openai.i1gpt2ca2t.xyz/openai/beta',//基础url如果海外服务器直接官方，如果国内服务器加海外云函数则自定义域名
    ],
    'api2d'       => [
        'forward_key'       => 'fk2159002-8FlWK4SLE342ZSA2CNHHwyXL822Hre8a0kOZs',
        'model'             => 'gpt-3.5-turbo', //模型
        "stream"            => true, //建议流输出，体验好
        'temperature'       => 1.0,
        'max_tokens'        => 0,
        'frequency_penalty' => 1.0,
        'presence_penalty'  => 1.0,
    ],
    'filter_text' => [ //敏感词过滤数组,防止提问敏感问题导致ip被墙
        '测试一下',
    ]
];