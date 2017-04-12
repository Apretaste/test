<h1>Dominios activos</h1>
<p>{$total} dominios activos</p>
<br>
{foreach from=$domains item=domain}
	{link href="AYUDA" caption="{$domain}"}<br/>
{/foreach}
<br><br>
{button href="TEST FULL" caption="Probar todos" size="small"}
