# api.decrypt.zlgccn.com 开发说明

## 项目定位

这是一个基于 ThinkPHP 6 的后端服务，主要承担两类职责：

1. SSO 登录、注册、刷新令牌、用户信息查询、退出登录。
2. 解密编辑器的数据接口，包括项目树、按钮点、提示点、剧情点、资源与发布相关接口。

前端工程 [sso_prod_client](../sso_prod_client/SKILL.md) 直接依赖这里的 token 机制和项目树接口，两个仓库通常要一起改。

## 技术栈

- ThinkPHP 6 + ORM
- JWT 鉴权，使用 `firebase/php-jwt`
- 中间件鉴权，依赖请求头 `access-token` 和 `refresh-token`
- 数据库驱动通过配置文件和环境变量控制

## 本地开发

- 安装依赖：`composer install`
- 本地启动：`php think run`（如果当前环境启用了 ThinkPHP 控制台命令）
- 生产部署：通常由 Nginx 或 Apache 指向 `public/index.php`

## 关键路由

- `POST /sso/user/login`：登录并返回 `access_token` / `refresh_token`
- `POST /sso/user/register`：注册新账号
- `POST /sso/user/refreshToken`：刷新令牌
- `GET /sso/user/userinfo`：读取当前用户信息
- `GET /sso/user/logout`：退出登录
- `POST /sso/user/checkToken`：校验 token 有效性，前端首页会调用
- `GET /v2/editor/auth/project`：获取当前用户的项目树
- `GET /v2/editor/hintPoint`、`POST /v2/editor/hintPoint`、`DELETE /v2/editor/hintPoint/condition`
- `GET /v2/editor/storyPoint/room`、`POST /v2/editor/storyPoint/room`、`DELETE /v2/editor/storyPoint/room`

## 鉴权流程

- 登录成功后，`app/controller/sso/user/Login.php` 会把 token 对写回 `sso_users` 表。
- `app/middleware/AuthMiddleware.php` 会从请求头读取 `access-token`，然后：
	- 先验 JWT 签名和过期时间
	- 再对比数据库里的 `access_token`
	- 不一致时返回“账号已在其他设备登录，请重新登录”
- `app/controller/sso/user/Auth.php::refreshToken()` 会校验 `refresh_token`，然后生成新的 token 对并更新数据库。

## 数据结构与接口特点

- `v2/editor/auth/project` 会一次性拼出 `project -> city -> room -> button_point -> hint_point` 的深层树形结构。
- 这个接口内部有明显的多层循环和多次数据库查询，修改时要注意性能和 N+1 查询。
- 按钮点会根据 `type` 映射到不同的扩展表，例如 `button_point_tip`、`button_point_draggable`、`button_point_door`、`button_point_item` 等。
- `sub_resource_type === 2` 时还会额外查 `animation_frames`，并补出 `animation_action_path`。

## 开发注意点

- `app/middleware/Cors.php` 和 `public/index.php` 都在处理跨域，规则不一致时优先统一，否则前端调试容易出现偶发跨域问题。
- 路由里使用了 `\app\middleware\Cors::class` 这种写法，Linux 环境下类名和文件名大小写要严格一致。
- `config/jwt.php` 里有默认的 `access_key` / `refresh_key`，生产环境应尽量通过环境变量覆盖，不要依赖默认值。
- 数据库配置、JWT 配置和跨域来源都不应该长期保留开发默认值。
- 这个项目大量使用全局辅助函数 `success()` / `error()`，改接口时要保持返回结构一致，否则前端拦截器会失效。

## 目录导航

- `app/controller/sso/user/`：登录、注册、刷新、用户信息、退出登录
- `app/controller/v2/editor/`：编辑器主接口
- `app/middleware/`：鉴权和跨域
- `config/`：应用、数据库、JWT、路由、缓存等配置
- `route/`：路由入口，`route/app.php` 会加载 `route/v2/*.php`
- `public/index.php`：HTTP 入口与基础跨域头
- `app/common.php`：全局 `success()` / `error()` 封装

## 修改建议

- 改 token 逻辑时，要同步检查登录页、刷新逻辑、`AuthMiddleware` 和前端请求拦截器。
- 改项目树结构时，要同步检查项目列表接口、前端左侧树、按钮点与提示点的依赖。
- 新增编辑器接口时，优先挂在 `v2/editor` 下面，并继续复用现有的鉴权中间件。