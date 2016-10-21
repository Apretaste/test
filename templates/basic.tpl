<h1>Buzones</h1>
<ul>
{foreach from=$buzones item=item}
	<li>{link href="TEST {$item['mailbox']} {$item['service']}" caption="{$item['mailbox']}"}</li>
{/foreach}
</ul>
<center>
{button href="TEST FULL {$service}" caption="Probar todos"} 
</center>