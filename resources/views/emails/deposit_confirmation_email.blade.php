@component('mail::message')
# Deposit Confirmation: Your Funds Are Now Available!

Dear **{{ $transaction->user->name }}**,

We are delighted to inform you that your recent deposit has been successfully processed. The funds are now credited to your account, and you can begin enjoying our services without any delay.

Here are the details of your deposit:

- **Transaction ID**: {{ $transaction->transaction_number }}
- **Deposit Amount**: $ {{ $transaction->transaction_amount }}
- **Date and Time**: {{ $transaction->created_at }}

If you have any questions regarding your deposit or need assistance with anything else, please feel free to contact our customer support team. We are here to help you. Thank you for choosing Lucky Ant Trading. We appreciate your trust and look forward to serving you.

Best Regards ,
Lucky Ant Trading Ltd Team

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

# 存款确认：您的资金现已可用！

尊敬的**{{ $transaction->user->name }}**,

我们非常高兴地通知您,您最近的存款已经成功处理。资金现已存入您的账户,您可以立即开始享受我们的服务。

以下是您存款的详细信息：

- **交易ID**: {{ $transaction->transaction_number }}
- **存款金额**: $ {{ $transaction->transaction_amount }}
- **日期和时间**: {{ $transaction->created_at }}

如果您对存款有任何疑问或需要帮助,请随时联系我们的客户支持团队。我们在这里为您提供帮助。感谢您选择蚂蚁科技。我们感谢您的信任,期待为您服务。

此致敬礼,<br>
Lucky Ant Trading Ltd Team

@component('mail::subcopy')
"免责声明 - 尽管经过深入研究编制了上述内容,但它纯粹作为信息和教育材料提供。所提供的任何内容都不应被解释为任何形式的投资建议。"
@endcomponent

@component('mail::table')
| 差价合约(CFD)是复杂的金融工具,存在由于杠杆导致的迅速资本损失的重大风险。必须评估您对CFD机制的理解,并评估您是否具备承担重大资本侵蚀风险的财务能力。 |
| ---------------------------------------------------------------------------------------------------------------------- |
| 在进行外汇,差价合约(CFD),点差投注或外汇期权交易之前,请阅读蚂蚁科技的风险披露。外汇/差价合约(CFD),点差投注和外汇期权交易涉及重大损失风险，不适合所有投资者。 |
| 版权所有 © 蚂蚁科技。保留所有权利。 |
@endcomponent

@endcomponent
