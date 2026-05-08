var axios = require('axios');

// 测试用例1: 不包含 room_id 的请求
var data1 = '{
    "id": 33,
    "name": "NewHintPoint",
    "type": 3,
    "param": {
        "hint_point_id": 33,
        "image_id": 264,
        "width": 117,
        "height": 117
    }
}';

// 测试用例2: 包含 room_id 的请求（正常情况）
var data2 = '{
    "id": 33,
    "room_id": 24,
    "name": "NewHintPoint",
    "type": 3,
    "param": {
        "hint_point_id": 33,
        "image_id": 264,
        "width": 117,
        "height": 117
    }
}';

var config = {
    method: 'post',
    url: 'https://api.decrypt.zlgccn.com/v1/editor/hintPoint',
    headers: {
        'access-token': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NzIwMDcxNjUsIm5iZiI6MTc3MjAwNzE2NSwiZXhwIjoxNzcyMDEwNzY1LCJ0eXBlIjoiYWNjZXNzIiwiZGF0YSI6eyJ1c2VySWQiOjEsInVzZXJuYW