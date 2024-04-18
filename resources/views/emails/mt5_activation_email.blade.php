@component('mail::message')
# MT5 Account Activation Confirmed

Dear **{{ $user->name }}**,

We are pleased to confirm that your MT5 trading account has been successfully opened. Thank you for choosing us as your forex broker. We are committed to providing you with the highest quality trading platform and services.

Here are the details of your MT5 trading account:

**Account Type**: MT5 Trading Account  
**Account Number**: {{ $metaAccount['login'] }}  
**Initial Deposit**: {{ $balance }}  
**Leverage**: 1:{{ $metaAccount['leverage'] }}  
**Trading Server**: {{ $metaAccount['server'] }}  
**Login**: {{ $metaAccount['login'] }}  
**Main Password**: {{ $metaAccount['mainPassword'] }}  
**Investor Password**: {{ $metaAccount['investPassword'] }}

You can use the above login credentials to access our trading platform and begin trading. If you have any questions about the MT5 trading platform or need assistance, please feel free to contact our customer service team. Our staff is dedicated to providing you with support and assistance.

Best Regards,  
Lucky Ant Trading Team

@component('mail::subcopy')
"Disclaimer - Despite thorough research to compile the above content, it serves purely as informational and educational material. None of the content provided should be construed as investment advice in any form."
@endcomponent

@component('mail::table')
| CFD\'s represent intricate financial instruments and pose a substantial risk of rapid capital loss, primarily attributable to leverage. It is imperative to assess your comprehension of CFD mechanics and evaluate whether you possess the financial capacity to assume the considerable risk of capital erosion. |
| ---------------------------------------------------------------------------------------------------------------------- |
| Read Lucky Ant Trading risk disclosure before trading forex, CFD\'s, Spread - betting, or FX Options. |
| Forex / CFD\'s, Spread - betting, and FX Options trading involve substantial risk of loss and are not suitable for all investors. |
| Copyright © Lucky Ant Trading. All rights reserved. |
@endcomponent

# MT5账户激活已确认

尊敬的**{{ $user->name }}**,

我们很高兴地确认您的MT5交易账户已成功开设。感谢您选择我们作为您的外汇经纪商。我们承诺为您提供最高品质的交易平台和服务。

以下是您的MT5交易账户的详细信息:

**账户类型**: MT5交易账户  
**账户编号：**: {{ $metaAccount['login'] }}  
**初始存款：**: {{ $balance }}  
**杠杆比例**: 1:{{ $metaAccount['leverage'] }}  
**交易服务器： Server**: {{ $metaAccount['server'] }}  
**登录用户名：**: {{ $metaAccount['login'] }}  
**主密码：**: {{ $metaAccount['mainPassword'] }}  
**观察密码：**: {{ $metaAccount['investPassword'] }}

您可以使用以上登录凭据访问我们的交易平台并开始交易。如果您对MT5交易平台有任何疑问或需要帮助,请随时联系我们的客户服务团队。我们的工作人员致力于为您提供支持和协助。

此致敬礼, 蚂蚁科技团队

@component('mail::subcopy')
“免责声明 - 尽管经过深入研究编制了上述内容,但它纯粹作为信息和教育材料提供。所提供的任何内容都不应被解释为任何形式的投资建议。”
@endcomponent

@component('mail::table')
| 差价合约(CFD)是复杂的金融工具,存在由于杠杆导致的迅速资本损失的重大风险。必须评估您对CFD机制的理解,并评估您是否具备承担重大资本侵蚀风险的财务能力。 |
| ---------------------------------------------------------------------------------------------------------------------- |
| 在进行外汇，差价合约(CFD)，点差投注或外汇期权交易之前，请阅读蚂蚁科技的风险披露。外汇/差价合约(CFD)，点差投注和外汇期权交易涉及重大损失风险，不适合所有投资者。 |
| 版权所有 © 蚂蚁科技。保留所有权利。 |
@endcomponent

@endcomponent
