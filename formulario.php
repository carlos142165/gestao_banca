<?php

     if (isset($_POST['submit']) || (isset($_POST['nome']) && isset($_POST['email']))) 
     
    {
        

        include_once('config.php');

        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $password = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $Telefone = $_POST['telefone'];
        $sexo = $_POST['genero'];

        // Verifica se o e-mail já existe no banco
        $checkEmail = mysqli_prepare($conexao, "SELECT id FROM usuarios WHERE email = ?");
        mysqli_stmt_bind_param($checkEmail, "s", $email);
        mysqli_stmt_execute($checkEmail);
        mysqli_stmt_store_result($checkEmail);

        if (mysqli_stmt_num_rows($checkEmail) > 0) {
        echo "Este e-mail já está cadastrado!";
        exit;
       }

        $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios(nome, email, senha, telefone, genero) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $nome, $email, $password, $Telefone, $sexo);
        mysqli_stmt_execute($stmt);

        echo "Cadastro efetuado com sucesso!";
        exit;
    }

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario GN</title>

    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            background-image: linear-gradient(to right, #255f75, #1d4d5f);
        }
        .box{
            color: #eeeded;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%,-50%);
            background-color: #113647;
            padding: 15px;
            border-radius: 15px;
            width: 80%;
            max-width: 400px; 
            

        }

        fieldset{
            border: 3px solid #255f75;
        }

        legend{
            border: 1px solid #255f75;
            padding: 10px;
            text-align: center;
            background-color: #255f75;
            border-radius: 5px;
            
        }

        .inputbox{
            position: relative;

        }
        .inputUser{
            background: none;
            border: none;
            border-bottom: 1px solid #255f75;
            width: 100%;
            outline: none;
            color: #eeeded;
            font-size: 15px;
            letter-spacing: 1px;
            box-sizing: border-box;
        }

        .labelinput{
            position: absolute;
            top: 0px;
            left: 0px;
            pointer-events: none;
            transition: .5s;
            font-size: 15px;
        }

        .inputUser:focus ~ .labelinput,
        .inputUser:valid ~ .labelinput{
            top: -20px;
            font-size: 12px;
            color: #2d7592;
        }

        #submit{
            background-color: #255f75;
            width: 100%;
            border: none;
            padding: 15px;
            border-radius: 10px;
            color: #eeeded;
            font-size: 15px;
            cursor: pointer;

        }
        
        /* CODIGO RESPONSAVEL AO PASSAR O MOUSE MUDA A COR */
        #submit:hover{
            background-color: #1e5165;

        }

        /* CODIGO RESPONSAVEL EM DEIXAR MAIUCULA E MINUSCULA */
        #nome{
            text-transform: capitalize;
        }



         /* CODIGO RESPONSAVEL EM DAR ESTILO A VISUALIZAÇÃO DA SENHA */
        .inputbox {
             position: relative;
             cursor: pointer;
        }

        .toggle-password {
             position: absolute;
             top: 50%;
             right: 0px;
             transform: translateY(-125%);
             cursor: pointer;
             font-size: 18px;
             color: #ccc;
             user-select: none;
             max-height: -125%;
        }

         .inputSenha {
             padding-right: 30px; /* espaço para o ícone */
        }
        /* FIM DO CODIGO RESPONSAVEL EM DAR ESTILO A VISUALIZAÇÃO DA SENHA */


        


        .caps-aviso {
            position: absolute;
            top: 100%;
            left: 0;
            font-size: 12px;
            color: rgb(137, 137, 29);
            margin-top: -21px;
            visibility: hidden;
            height: 16px; /* reserva espaço */
        }




    </style>

 



</head>

<body>

    <div class="box">
        <form action="formulario.php" method="POST" >
            <fieldset>
                <legend><b>Cadastro</b></legend>
                <br>

                <div class="inputbox">
                    <input type="text" name="nome" id="nome" class="inputUser" required>
                    <label for="nome" class="labelinput">Nome Completo</label>
                </div>
                <br><br>

                <div class="inputbox">
                    <input type="email" name="email" id="email" class="inputUser" required>
                    <label for="email"class="labelinput">Email</label>
                </div>
                <br><br>


                <div class="inputbox">
                    <input type="password" name="senha" id="senha" class="inputUser inputSenha" required>
                    <label for="senha" class="labelinput">Senha</label>
                    <span class="toggle-password" onclick="togglePasswordVisibility()">👁️‍🗨️</span>
                    <div id="caps-lock-warning" class="caps-aviso">⚠️ Caps Lock está ativado!</div>
                </div>

                <br><br>

                <div class="inputbox">
                    <input type="tel" name="telefone" id="telefone" class="inputUser" required>
                    <label for="telefone"class="labelinput">Telefone</label>
                </div>
                <br>

                <p>Sexo</p>
                <input type="radio" id="feminino" name="genero" value="feminino" required>
                <label for="feminino">Feminino</label>
                <br>

                <input type="radio" id="masculino" name="genero" value="masculino" required>
                <label for="masculino">Masculino</label>
                <br>

                <input type="radio" id="outros" name="genero" value="outros" required>
                <label for="outros">Outros</label>
                <br><br>
                <br>

                <input type="submit" name="submit" id="submit">
                <br>


            </fieldset>    
        </form>        
    </div>



     
    <script> // CODIGO RESPONSAVEL EM TRATAR A FORMATAÇÃO DO TELEFONE
           async function detectarCodigoPais() {
           try {
             const resposta = await fetch('https://ipapi.co/json/');
             const dados = await resposta.json();
             const codigoPais = dados.country_calling_code; // Ex: +55
             const telefoneInput = document.getElementById('telefone');

            if (telefoneInput && !telefoneInput.value.startsWith(codigoPais)) {
              telefoneInput.value = codigoPais + ' ';
            }

            } catch (erro) {
             console.warn('Não foi possível detectar o país:', erro);
            }
        } 

        function aplicarMascaraTelefone() {
             const telefoneInput = document.getElementById('telefone');

             telefoneInput.addEventListener('input', function(e) {
             let valor = e.target.value.replace(/\D/g, '');
      
             // Remove código do país se já tiver sido adicionado
              if (valor.startsWith('55')) {
              valor = valor.slice(2);
        }

              if (valor.length > 11) valor = valor.slice(0,11);

              valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2');
              valor = valor.replace(/(\d{5})(\d{1,4})$/, '$1-$2');

              // Adiciona código do país manualmente na frente
              e.target.value = '+55 ' + valor;
             });
        }

             window.onload = function() {
             detectarCodigoPais();
             aplicarMascaraTelefone();
        };

        // FIM DO CODIGO RESPONSAVEL EM TRATAR A FORMATAÇÃO DO TELEFONE



        // CODIGO RESPONSAVEL EM TRATAR A VISUALIZAÇÃO DA SENHA
        function togglePasswordVisibility() {
        const senha = document.getElementById("senha");
        const icone = document.querySelector(".toggle-password");

         if (senha.type === "password") {
         senha.type = "text";
         icone.textContent = "🙈";
         } else {
         senha.type = "password";
         icone.textContent = "👁️‍🗨️";
         }
        }


        const passwordInput = document.getElementById("senha");
        const capsLockWarning = document.getElementById("caps-lock-warning");

         passwordInput.addEventListener("keyup", function(event) {
           if (event.getModifierState("CapsLock")) {
           capsLockWarning.style.visibility = "visible";
           } else {
           capsLockWarning.style.visibility = "hidden";
        }
        });
        // FIM DO CODIGO RESPONSAVEL EM TRATAR A VISUALIZAÇÃO DA SENHA



    </script> 
    

    

    

                
            

    
</body>
</html>