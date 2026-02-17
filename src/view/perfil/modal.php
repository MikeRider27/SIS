<div class="modal fade" id="modal-agregar">
    <div class="modal-dialog">
        <form id="form" class="user">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h4 class="modal-title">Cambiar Avatar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-default">
                                <div class="card-header">
                                </div>
                                <div class="card-body">
                                    <div id="actions" class="row">
                                        <div class="col-lg-12">
                                        <input type="hidden" class="form-control rounded-0" name="code" id="code" placeholder="" value="<?= $usuario['persona_id']; ?>">
                                            <div class="custom-file">


                                                <label class="btn btn-primary" for="my-file-selector">
                                                    <input type="file" name="avatar" id="avatar">
                                                </label>


                                            </div>
                                        </div>

                                    </div>


                                </div>
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-warning" data-dismiss="modal">Cerrar</button>
                    <input type="hidden" name="accio" value="agregar">
                    <button id="guardar" type="submit" class="btn btn-success">Guardar</button>
                </div>
            </div>
        </form>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>