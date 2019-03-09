<?php
/**
 * Created by PhpStorm.
 * User: run
 * Date: 2019/3/3
 * Time: 13:18
 */


return [
    'alipay' => [
        'app_id' => '2016091800543103',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmaFvVsuQ4ONajYU8sVL4fO6XGYPae89XWD15m1EmSk7oz7RAy672OOQqeFjx9uZYXgvphCO1S79CbeCoivLiH+SsBPsju5ytBvHHbAFrP/6qqf2UYS3bAAeKHooSyBIXxsFl1EKphzSyFoi7gkF26bM/Vp6KorqohqAwKrjZWOf/z6CCUFXXyIDxUA5UbySfAXx0PDUSiJ/pjFRi5rZGjOX7sRfHvniNexaEG27qscU2DWZ/zymTmZ3KPCokMJ7cErTghBWfTn1KWV7lijItAPfICQF7G4FnUXssgpAFNIkfuWsas9g+p7EyWjfRd8sUO6+p3d8G1s5LVjRveVguUwIDAQAB',
        'private_key' => 'MIIEpAIBAAKCAQEAxPDMcSwC8De6KS/sP/K34WUwsgyh5oLUPhTxqdOB+ov4gpn2yzD+vtfxUqkSCqP2IQWhP3M6XZtm0/7C9coinaCmamM7NK5oU28QZOYA2On5NxQvRlMIDSz7Ig1zr4zeECSKfx3uXX1iFEH1JANtaiHixngnvPVa8dSsDZ7aBCqzgO6nvVhcf7lA9XINIr6+HtLXdrKPhfbUQBU/8U5RTQgEHl35CH+KP9JUIksVpclLT9ZxNiYM+wxTXDkGelIrpjnypHDQ8BUtB5IZyS5l6AOFpD2RyX8CuiJvkVzi01EmwMeCGO1XOwH97ygx5/begfm4GTZ6tzJw+BOvPMcOrQIDAQABAoIBABYhCJfe3iWn3V8rZ1x4JXlKKYKYMMj6AmdHazAt9/jzKWVjb3u9caJ4GUG0hbZ0Xf+v5kJ+7BwAjjlb8wQzRlh36Lsjk0+EGWzmmez8ezPkdoBd4EICqnDwkPduk4UySvF9aaKG9nXC1PZ5wtXdHLpEPHsYnT56Q1HRF9Qf+VdmUkOOtFW1aAqyvkLYzKQ7cpBoi2CLbWk9pDKNEw/VNfbBHHhkOqqVvfgrAJg9hfAxpzXuZMMsRZUB+fuKfIUSve0aDd4F/8CPlhRt0SdnSNZnyYYgsT/rz9CJRskRAk281uEip4pJIndPXubsxbLPC3xOdWtBw1Y+BjT3+qYywIECgYEA9Y8h9sKCCD5Q1yztAMlFutCdX43M88CVUn4QcoUUbtW3DLmon3OPyUP8mhks/mz2Yz3H+XHqM/rjF2RYCeug4Zkbv6CKjZ2Nv0it0+vnkNupmIXeMSdFvuLCcReFFFRxaKZE9yo5oY0j5JGjDQnKGtT5v/tRaKfOTNzDdSDD/okCgYEAzVB2V42eXkOJMmJY2Llq3wRm1x/dea3p3qPMtQ5+YE/bWlkkfM3LuCgo2uD/AXg/QD7qW+au9XZfgDEDm+34hSetmcYp68STtniIlKqBpOBjYmoUlm009qdc9Vsed+hySPNh12FCfi5KWI4zW6uIdfd3N+IhMO4cGFJxMj8k5gUCgYBiESiSQLnniuOEG6gHIVqcsgW3jIzmZ+n6iOgOpEE1xloVVewWyfMJgQJXlGhYr7FyjtDXOPw8iBy7UdKrG5QjpJ7lV7sdtWdPljn9oX/YZTGE/SlwXevHwQ3AVpFKPjMQhR6TyyQ69X/5H4SLh3ZUYuvfFQI9Fo8YOS5CD7TWaQKBgQCP+iri/vbd17JwWLNBV9VwC2Aq37eqSqFEdc1p8n4BAD8svnJt6ss/mzn7M+jfmPmSDgy+4agzjg0ukjCbumeayNZeja58HWAQh7oPtvovKwPG0ekaC/8mMPtpO7rED4eFTNeB+Dxuy/tq2l19nW6WezFpSPRJodCl5bpnqAwyGQKBgQC6YlDedYfI0I6b9RFjJ86hUjSOUE1UEM7TU16oUUGL5TkHxSuOHHmkBHQIqimI8Rot/F2soBDfCa7D3yxWKPemRlyRHRLjjoLlf+3w6N+QUeWIGvYpnfhZ6BEO3Q3qjnF+NQiSIgWOnihGBwjT7HHgb5lCffDqRSoQbQ3i12T1Eg==',
        'log' => [
            'file' => storage_path('logs/alipay.log')
        ]
    ],
    'wechat' => [
        'app_id' => '',
        'mch_id' => '',
        'key' => '',
        'cert_client' => '',
        'cert_key' => '',
        'log' => [
            'file' => storage_path('logs/wechat_pay.log')
        ]
    ],

];