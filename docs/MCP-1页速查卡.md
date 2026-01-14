# MCP 1页速查卡（团队用）

## 你每天只需要做这 4 件事
- 开工前：先在 MCP 创建/认领卡片（Feature/Bug/Release）
- 写代码前：确认卡片写清“输出物+验收标准+截止时间”
- 提 PR 时：在 PR 描述里写 `Closes #任务ID`
- 站会时：只看 MCP 看板，口头同步必须回写到卡片

## 三条硬规则（违反=无法上线）
- 不用 MCP = 无法推进到 Done
- PR 不关联任务 = 不允许合并
- Release Checklist 未完成 = 不允许发布

## 缺陷优先级
- P0：立即止血 + 30分钟内补齐卡片/PR/复盘
- P1：当天闭环或给出降级方案
- P2/P3：进迭代池

## 常用模板（可直接复制）
### 给 Tech Lead
“@TechLead，请在 D+3 前输出《字段字典与状态机（MVP版）》：仅 Feature/Bug/Release 三类对象；字段≤5；状态机用 Mermaid；验收：PO 可据此配置 MCP 模板。”

### 给 QA
“@QA，请在 D+4 前组织 3 人试跑：P0缺陷→关联任务→PR→CI→Checklist→通知，输出《试跑记录》含截图/耗时/卡点；验收：全流程≤30分钟、无手工干预。”

### 给 SRE
“@SRE，请在 D+5 前完成：PR 合并自动推进任务状态；Release checklist 完成自动通知；验收：提供可复用 workflow 与测试日志。”

