@component('mail::message')
# Daily Report for PAMM Subscription

Dear **{{ $user->name }}**,

Presented below is the daily profit report for your PAMM subscriptions.

**Date**: {{ $pamm['date'] }} 

@foreach($pamm['master_group'] as $pammData)
**Master Info**<br>
Account Number : {{ $pammData['master_meta_login'] }}<br>
Name : {{ $pammData['master_name'] }}<br>
Total Lot : {{ $pammData['total_master_lot'] }}<br>
Total Profit and Loss : ${{ $pammData['total_master_profit_and_loss'] }}<br>

@foreach ( $pammData['personal'] as $personal)
**Investment Info**<br>
Account Number : {{ $personal['meta_login'] }}<br>
Investment Amount : ${{ $personal['subscription_amount'] }}<br>
Join Date : {{ $personal['join_date'] }}<br>

**Trade results transactions**
@component('mail::table')
| **Closing Time**    | **Ticket** | **Symbol** | **Type** | **Lot** | **Profit ($)** |
|:--------------------|:-----------|:-----------|:---------|:--------|:---------------|
@foreach($personal['details'] as $detail)
| {{ $detail['time_close'] }} | {{ $detail['ticket'] }} | {{ $detail['symbol'] }} | {{ $detail['trade_type'] }} | {{ $detail['volume'] }} | {{ $detail['trade_profit'] }} |
@endforeach
|                     |            |            |          | **Total:** | **{{ $personal['profit_and_loss'] }}** |
@endcomponent

---

@endforeach

@endforeach


Important note: Please report to us within 24 hours if this statement is incorrect. Otherwise this statement will be considered to be confirmed by you.

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

# PAMM订阅每日报告

尊敬的**{{ $user->name }}**,

下面显示的是您的PAMM订阅的每日利润报告。

**日期**: {{ $pamm['date'] }} 

@foreach($pamm['master_group'] as $pammData)
**交易者信息**<br>
账户编号 : {{ $pammData['master_meta_login'] }}<br>
名字 : {{ $pammData['master_name'] }}<br>
总手数 : {{ $pammData['total_master_lot'] }}<br>
损益总额 : ${{ $pammData['total_master_profit_and_loss'] }}<br>

@foreach ( $pammData['personal'] as $personal)
**投资信息**<br>
账户编号 : {{ $personal['meta_login'] }}<br>
投资金额 : ${{ $personal['subscription_amount'] }}<br>
加入日期 : {{ $personal['join_date'] }}<br>

**交易结果**
@component('mail::table')
| **收盘时间**         | **票号** | **产品** | **类型** | **手数** | **盈利 ($)** |
|:--------------------|:-----------|:-----------|:---------|:--------|:---------------|
@foreach($personal['details'] as $detail)
| {{ $detail['time_close'] }} | {{ $detail['ticket'] }} | {{ $detail['symbol'] }} | {{ $detail['trade_type'] }} | {{ $detail['volume'] }} | {{ $detail['trade_profit'] }} |
@endforeach
|                     |            |            |          | **总盈利：** | **{{ $personal['profit_and_loss'] }}** |
@endcomponent

---

@endforeach

@endforeach


重要提示：如果此声明有误，请在 24 小时内向我们举报。否则本声明将被视为您已确认。

此致敬礼, 蚂蚁科技团队

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
