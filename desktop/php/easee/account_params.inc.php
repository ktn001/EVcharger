<div class='form-group'>
	<label class="col-sm-3 control-label">{{Login}}</label>
	<div class="col-sm-7">
		<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="login" placeholder="{{user}}"/>
	</div>
</div>
<div class='form-group'>
	<label class="col-sm-3 control-label">{{Password}}</label>
	<div class="col-sm-7">
		<div class="input-group" style="display:flex">
			<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="{{password}}"/>
			<button class="btn btn-outline-secondary show-pwd" type="button"><i class="fas fa-eye"></i></button>
			<button class="btn btn-outline-secondary hide-pwd" type="button" style="display:none"><i class="fas fa-eye-slash"></i></button>
		</div>
	</div>
</div>
<div class='form-group'>
	<label class="col-sm-3 control-label">{{URL}}</label>
	<div class="col-sm-7">
		<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="url" placeholder="https://api.easee.cloud"/>
	</div>
</div>

<script>
	/*
	 * Montre ou cache le password saisi
	 */
	$('.show-pwd').off('click').on('click',function() {
		$(this).closest('.input-group').find('input[type=password]').attr('type','text');
		$(this).hide();
		$(this).closest('.input-group').find('button.hide-pwd').show();
	});
	$('.hide-pwd').off('click').on('click',function() {
		$(this).closest('.input-group').find('input[type=text]').attr('type','password');
		$(this).hide();
		$(this).closest('.input-group').find('button.show-pwd').show();
	});
</script>
