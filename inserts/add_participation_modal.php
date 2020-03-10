<div class="modal fade" id="add-participation-modal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Ajouter une participation</h4>
			</div>
			<div class="modal-body container-fluid">
				<form class="form-horizontal no-submit" id="add-participation-form">
					<div class="form-group">
						<label for="user_identity" class="col-lg-4 control-label">Identité</label>
						<div class="col-lg-8">
							<input type="text" class="form-control name-input" data-filter="active" name="user_identity">
						</div>
					</div>
				</form>
				<div class="user-load">
					<p class="load-result"></p>
					<div class="user-loading-results loading-container">
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button class="btn btn-primary add-participation">Ajouter</button>
			</div>
		</div>
	</div>
</div>
