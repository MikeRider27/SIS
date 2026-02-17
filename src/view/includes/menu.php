<nav class="mt-2">

<?php if($_SESSION['idUsuario'] == '2900223'){ ?>
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                    Lacpass IPS
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">                
                <li class="nav-item">
                    <a href="/consultas/create/IPS" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Generar IPS</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/consultas/create/paciente" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-65 MHD IPS-LAC</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/paciente/ips" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-67</p>
                    </a>
                </li>  
                <li class="nav-item">
                    <a href="/paciente/view" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>IPS View</p>
                    </a>
                </li>             
            </ul>
        </li>
       <!-- <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                FHIR terminology
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/paciente/translate" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Translate</p>
                    </a>
                </li>   
                <li class="nav-item">
                    <a href="/paciente/lookup" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>lookup</p>
                    </a>
                </li>                            
            </ul>
        </li>-->
        <li class="nav-item">
            <a href="#" class="nav-link">
               <i class="nav-icon fas fa-syringe text-secondary"></i>

                <p>
                ICVP
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/consultas/create/vacunacion" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-65 MHD IPS-ICVP</p>
                    </a>
                </li>              
            </ul>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/icvp/vaccine" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ICVP QuestionnaireResponse</p>
                    </a>
                </li>              
            </ul>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/paciente/ips/icvp" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Generar ICVP</p>
                    </a>
                </li>              
            </ul>
            
        </li>      
        <!--<li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-server text-secondary"></i>
                <p>
                    Qualification PDQm
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/qualification/iti-78/search" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-78</p>
                    </a>
                </li>              
            </ul>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-server text-secondary"></i>
                <p>
                    Qualification PIXm
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/qualification/iti-104/create" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-104</p>
                    </a>
                </li>              
            </ul>
        </li>-->
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-server text-secondary"></i>
                <p>
                GDHCN Validator
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/paciente/vhl_generar" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>VHL</p>
                    </a>
                </li>              
            </ul>
        </li>
   <!-- <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-paperclip text-secondary"></i>
                <p>
                DATOS
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/datos/personal" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Personal</p>
                    </a>
                </li>              
            </ul>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/datos/paciente" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Paciente</p>
                    </a>
                </li>              
            </ul>
             <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/datos/servicio" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Servicio</p>
                    </a>
                </li>              
            </ul>

        </li>-->
         <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                    Pacientes
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">  
                 <li class="nav-item">
                    <a href="/pacientes/create" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Crear Paciente</p>
                    </a>
                </li>             

                <li class="nav-item">
                    <a href="/pacientes/list" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Listar Pacientes</p>
                    </a>
                </li>  
                    
                         
            </ul>
        </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                    Profesionales
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">  
                 <li class="nav-item">
                    <a href="/profesional/create" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Crear Profesional</p>
                    </a>
                </li>             

                <li class="nav-item">
                    <a href="/profesional/list" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Listar Profesionales</p>
                    </a>
                </li>  
                    
                         
            </ul>
        </li>

            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-user text-secondary"></i>
                    <p>
                        Organizaciones
                        <i class="fas fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">  
                    <li class="nav-item">
                        <a href="/organizacion/create" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Crear Organización</p>
                        </a>
                    </li>             
    
                    <li class="nav-item">
                        <a href="/organizacion/list" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Listar Organizaciones</p>
                        </a>
                    </li>  
                        
                            
                </ul>
            </li>
     

         <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-cogs text-secondary"></i>
                <p>
                Configuration
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="/fhir/list" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>FHIR CONFIG</p>
                    </a>
                </li>              
            </ul>
        </li>
       
        
       

       
    </ul>
<?php }else if($_SESSION['idUsuario']== '5222465'){ ?>
 <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                    Visor IPS
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">  
                 <li class="nav-item">
                    <a href="/consultas/create/IPS-LAC" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-65 IPS-LAC</p>
                    </a>
                </li>             

                <li class="nav-item">
                    <a href="/itti67/search" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>ITI-67</p>
                    </a>
                </li>  
                <li class="nav-item">
                    <a href="/ips/view" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>IPS View</p>
                    </a>
                </li>             
            </ul>
        </li>

          <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                    Pacientes
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">  
                 <li class="nav-item">
                    <a href="/pacientes/create" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Crear Paciente</p>
                    </a>
                </li>             

                <li class="nav-item">
                    <a href="/pacientes/list" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Listar Pacientes</p>
                    </a>
                </li>  
                    
                         
            </ul>
        </li>

         <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-secondary"></i>
                <p>
                    Profesionales
                    <i class="fas fa-angle-left right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">  
                 <li class="nav-item">
                    <a href="/profesional/create" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Crear Profesional</p>
                    </a>
                </li>             

                <li class="nav-item">
                    <a href="/profesional/list" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Listar Profesionales</p>
                    </a>
                </li>  
                    
                         
            </ul>
        </li>

            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-user text-secondary"></i>
                    <p>
                        Organizaciones
                        <i class="fas fa-angle-left right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">  
                    <li class="nav-item">
                        <a href="/organizacion/create" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Crear Organización</p>
                        </a>
                    </li>             
    
                    <li class="nav-item">
                        <a href="/organizacion/list" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Listar Organizaciones</p>
                        </a>
                    </li>  
                        
                            
                </ul>
            </li>
     
        
       

       
    </ul>
    <?php } else { ?>
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

    <li class="nav-item">
        <a href="/paciente/view" class="nav-link">
            <i class="nav-icon fas fa-eye text-secondary"></i>
            <p>
                Visor RDA                 
            </p>
        </a>           
    </li>
    
    <li class="nav-item">
        <a href="/paciente/ips" class="nav-link">
            <i class="nav-icon fas fa-search text-secondary"></i>
            <p>
                Consulta RDA                 
            </p>
        </a>           
    </li>

    <li class="nav-item">
        <a href="/consultas/create/paciente" class="nav-link">
            <i class="nav-icon fas fa-list text-secondary"></i>
            <p>
                MHD RDA- ITI-65                 
            </p>
        </a>           
    </li>

    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-user-injured text-secondary"></i>
            <p>
                Pacientes
                <i class="fas fa-angle-left right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">  
            <li class="nav-item">
                <a href="/pacientes/create" class="nav-link">
                    <i class="fas fa-user-plus nav-icon"></i>
                    <p>Crear Paciente</p>
                </a>
            </li>             

            <li class="nav-item">
                <a href="/pacientes/list" class="nav-link">
                    <i class="fas fa-list-ul nav-icon"></i>
                    <p>Listar Pacientes</p>
                </a>
            </li>                         
        </ul>
    </li>
    
    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-user-md text-secondary"></i>
            <p>
                Profesionales
                <i class="fas fa-angle-left right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">  
            <li class="nav-item">
                <a href="/profesional/create" class="nav-link">
                    <i class="fas fa-user-plus nav-icon"></i>
                    <p>Crear Profesional</p>
                </a>
            </li>             

            <li class="nav-item">
                <a href="/profesional/list" class="nav-link">
                    <i class="fas fa-list-ul nav-icon"></i>
                    <p>Listar Profesionales</p>
                </a>
            </li>                         
        </ul>
    </li>

    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-hospital text-secondary"></i>
            <p>
                Organizaciones
                <i class="fas fa-angle-left right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">  
            <li class="nav-item">
                <a href="/organizacion/create" class="nav-link">
                    <i class="fas fa-plus-circle nav-icon"></i>
                    <p>Crear Organización</p>
                </a>
            </li>             

            <li class="nav-item">
                <a href="/organizacion/list" class="nav-link">
                    <i class="fas fa-list-ul nav-icon"></i>
                    <p>Listar Organizaciones</p>
                </a>
            </li>                         
        </ul>
    </li>
</ul>
<?php } ?>


</nav>