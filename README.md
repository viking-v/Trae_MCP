
Closes #3
# 梦之缘平台（方案B：后端可信化）

## 结构
- 前端静态页：根目录 `*.html` 与 `js/*`
- 后端（Laravel）：[backend/](file:///E:/make/CZOWPcVFHILt2YYVJOp0-master-6d735223c1f1d4da5289c50b873d3d8cb50f54d3/backend)
- 可视化质量报告：`reports/plato/`
- 方案执行手册：`docs/方案B-执行手册.md`
- 上线准备计划：`docs/上线准备计划.md`
- 架构与依赖可视化：`docs/架构与依赖可视化.md`
- MCP工具选型与实施方案：`docs/MCP工具选型与实施方案.md`
- MCP使用公约：`docs/MCP使用公约.md`
- MCP字段字典与状态机（MVP）：`docs/MCP-字段字典与状态机-MVP.md`
- MCP 1页速查卡：`docs/MCP-1页速查卡.md`
- 项目进度与关键路径执行计划：`docs/项目进度与关键路径执行计划.md`
- 周报模板：`docs/周报模板.md`

## 本地运行（开发）
### 1) 启动后端 API（8000）
在 `backend/` 目录：
- `php artisan migrate:fresh --seed`
- `php artisan serve --host=127.0.0.1 --port=8000`

默认管理员：
- 邮箱：`admin@example.com`
- 密码：`admin123456`

### 2) 启动前端静态服务（3000）
在仓库根目录：
- `python -m http.server 3000`

访问：
- `http://localhost:3000/index.html`

## API 概览
- 认证：`POST /api/auth/register`、`POST /api/auth/login`、`POST /api/auth/logout`、`GET /api/auth/user`
- 用户侧：`POST /api/activations`、`GET /api/activations/me`、`GET /api/commissions/me`、`GET /api/team/me`、`GET /api/team/me/tree`、`GET /api/invite-codes/me`
- 管理侧：`GET /api/admin/dashboard`、`GET /api/admin/users`、`PATCH /api/admin/users/{id}`、`GET /api/admin/activations`、`POST /api/admin/activations/{id}/approve|reject`、`GET /api/admin/commissions`
