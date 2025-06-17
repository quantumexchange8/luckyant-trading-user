@component('mail::message')
# One - Time Password Notification

Hello ,

Verification Code : **{{ $otp }}**,

We noticed that you started the registration process for a forex trading account with Lucky Ant Trading. By opening a real forex trading account with Lucky Ant Trading, you'll have access to a wide range of forex pairs, as well as tools and resources to help you make informed trades. Plus, our experienced team is always available to support and guide you(support@luckyantfxasia.com).

Best Regards ,<br>
Lucky Ant Trading Ltd Team

@component('mail::subcopy')
"Disclaimer - Despite thorough research to compile the above content, it serves purely as informational and educational material. None of the content provided should be construed as investment advice in any form."
@endcomponent

@component('mail::table')
| CFD\'s represent intricate financial instruments and pose a substantial risk of rapid capital loss, primarily attributable to leverage. It is imperative to assess your comprehension of CFD mechanics and evaluate whether you possess the financial capacity to assume the considerable risk of capital erosion. |
| ---------------------------------------------------------------------------------------------------------------------- |
| Read Lucky Ant Trading risk disclosure before trading forex, CFD\'s, Spread - betting, or FX Options. |
| Forex / CFD\'s, Spread - betting, and FX Options trading involve substantial risk of loss and are not suitable for all investors. |
<br>
| Copyright © Lucky Ant Trading. All rights reserved. |
@endcomponent

# 一次性密码通知

您好，

验证代码 : **{{ $otp }}**,

我们注意到您已经开始了在蚂蚁科技进行外汇交易账户注册的过程。通过在蚂蚁科技开设真实的外汇交易账户,您将能够访问各种外汇交易对,以及有助于您做出明智交易的工具和资源。此外,我们经验丰富的团队随时可为您提供支持和指导 (support@luckyantfxasia.com)。

此致敬礼,<br>
Lucky Ant Trading Ltd Team

@component('mail::subcopy')
"免责声明 - 尽管经过深入研究编制了上述内容,但它纯粹作为信息和教育材料提供。所提供的任何内容都不应被解释为任何形式的投资建议。"
@endcomponent

@component('mail::table')
| 差价合约(CFD)是复杂的金融工具,存在由于杠杆导致的迅速资本损失的重大风险。必须评估您对CFD机制的理解,并评估您是否具备承担重大资本侵蚀风险的财务能力。 |
| ---------------------------------------------------------------------------------------------------------------------- |
| 在进行外汇,差价合约(CFD),点差投注或外汇期权交易之前,请阅读蚂蚁科技的风险披露。外汇/差价合约(CFD),点差投注和外汇期权交易涉及重大损失风险，不适合所有投资者。 |
<br>
| 版权所有 © 蚂蚁科技。保留所有权利。 |
@endcomponent

@endcomponent
