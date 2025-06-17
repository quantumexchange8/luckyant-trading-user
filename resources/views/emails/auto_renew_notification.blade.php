@component('mail::message')
# A Notification of Automatic Renewal Copy Trading Subscription

Dear **{{ $user_name }}**,

Your Copy Trading period is nearing its end, and our system is set to automatically renew it unless you wish to unsubscribe. You can log in to the member system to take any necessary actions.

Thank you for choosing our service. Should you have any questions or require assistance, please feel free to contact our customer service team. We are here to assist you.

Best Regards,
Lucky Ant Trading Ltd Team

@component('mail::subcopy')
"Disclaimer - Despite thorough research to compile the above content, it serves purely as informational and educational material. None of the content provided should be construed as investment advice in any form."
@endcomponent

@component('mail::table')
| CFD\'s represent intricate financial instruments and pose a substantial risk of rapid capital loss, primarily attributable to leverage. It is imperative to assess your comprehension of CFD mechanics and evaluate whether you possess the financial capacity to assume the considerable risk of capital erosion. |
| ---------------------------------------------------------------------------------------------------------------------- |
| Read Lucky Ant Trading risk disclosure before trading forex, CFD\'s, Spread - betting, or FX Options. |
| Forex / CFD\'s, Spread - betting and FX Options trading involves substantial risk of loss and is not suitable for all investors. |
| Copyright © Lucky Ant Trading. All rights reserved. |
@endcomponent

# 跟单交易订阅自动续订通知

尊敬的**{{ $user_name }}**,

您的跟单交易订阅期即将结束，我们的系统将默认为自动续订，除非您希望取消订阅，您可以登录会员系统进行相关操作。

感谢您选择我们的服务。如果您有任何疑问或需要帮助，请随时联系我们的客服团队。我们将竭诚为您提供支持。

此致敬礼,<br>
Lucky Ant Trading Ltd Team

@component('mail::subcopy')
“免责声明 - 尽管经过深入研究编制了上述内容，但它纯粹作为信息和教育材料提供。所提供的任何内容都不应被解释为任何形式的投资建议。”
@endcomponent

@component('mail::table')
| 差价合约（CFDs）是复杂的金融工具，存在由于杠杆导致的迅速资本损失的重大风险。必须评估您对CFDs机制的理解，并评估您是否具备承担重大资本侵蚀风险的财务能力。 |
| ---------------------------------------------------------------------------------------------------------------------- |
| 在进行外汇，差价合约（CFDs），点差投注或外汇期权交易之前，请阅读蚂蚁科技的风险披露。外汇/差价合约（CFDs），点差投注和外汇期权交易涉及重大损失风险，不适合所有投资者。 |
| 版权所有 © 蚂蚁科技。保留所有权利。 |
@endcomponent

@endcomponent
