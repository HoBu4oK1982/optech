<div class="container-fluid">    
    <a href="{{route('dashboard')}}"><i class="fa-solid fa-house"></i></a>&nbsp&nbsp/&nbsp&nbspРедактирование профиля
    <div class="row d-flex justify-content-center">
        <div class="col-md-8 border bg-light rounded mt-4">
            <div class="panel panel-default p-4">
                <div class="panel-heading">
                    <div class="row pt-4 pb-4">
                        <div class="col-md-6">
                            <h4>Редактирование профиля</h4>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" wire:submit.prevent='updateUser'>
                        <div class="input-group mb-3">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2">Имя</span>
                            </div>
                            <input type="text" required class="form-control" aria-describedby="basic-addon2" wire:model="name">                        
                        </div>

                        <div class="input-group mb-3">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2">Email</span>
                            </div>
                            <input type="text" disabled class="form-control" aria-describedby="basic-addon2" wire:model="email">                            
                        </div>

                        <h6>Изменить пароль</h6>

                        <div class="input-group mb-3">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2">Новый пароль</span>
                            </div>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" wire:model="password">                        
                        </div>

                        <div class="input-group mb-3">
                            <div class="input-group-append">
                                <span class="input-group-text" id="basic-addon2">Повторите новый пароль</span>
                            </div>
                            <input type="text" class="form-control" aria-describedby="basic-addon2" wire:model="password_confirmation">                        
                        </div>
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                        <div class="form-group">
                            <label for="" class="col-md-4 control-label">
                                
                            </label>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Сохранить</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>        
    </div>   
     
</div>
