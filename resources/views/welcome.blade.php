

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat</title>
    <style>
        @font-face {
            font-family: 'Ciutadella bold italic';
            src: url(../assets/fonts/ciutadella-bold-italic.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: italic;
        }

        @font-face {
            font-family: 'Ciutadella bold';
            src: url(../assets/fonts/ciutadella-bold.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella semibold italic';
            src: url(../assets/fonts/ciutadella-semibold-italic.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella semibold';
            src: url(../assets/fonts/ciutadella-semibold.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella light';
            src: url(../assets/fonts/ciutadella-light.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella light italic';
            src: url(../assets/fonts/ciutadella-light-italic.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella medium';
            src: url(../assets/fonts/ciutadella-medium.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella medium italic';
            src: url(../assets/fonts/ciutadella-medium-italic.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella regular';
            src: url(../assets/fonts/ciutadella-regular.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Ciutadella regular italic';
            src: url(../assets/fonts/ciutadella-regular-italic.ttf) format('truetype');
            /* font-weight: bold; */
            font-style: normal;
        }

        @font-face {
            font-family: 'Jacksilver';
            src: url(../assets/fonts/Jacksilver.ttf) format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --certificat-height: 800px;
            --certificat-width: 1200px;
        }

        * {
            padding: 0px;
            margin: 0px;
        }

        .main {
            margin: auto;
            position: relative;
            height: var(--certificat-height);
            width: var(--certificat-width);
            background-image: url(../assets/certificat-bg.png);
            background-position: center center;
            background-clip: content-box;
            background-repeat: no-repeat;
            background-size: cover;
            background-color: #fbf8e7;
        }

        #Certificat-background {
            top: 0px;
            z-index: -1;
        }

        /* #logo {
            position: absolute;
            width: 200px;
            left: 20%;
            top: 3%;
        } */

        #logo {
            position: absolute;
            width: 200px;
            left: 40%;
            top: 0;
            /* bottom: 10%; */
        }

        .title {
            position: absolute;
            left: 31%;
            top: 18%;
            font-weight: bold;
            font-size: 88px;
            font-family: 'Ciutadella bold';
        }

        .sub-title {
            position: absolute;
            left: 40%;
            top: 30%;
            font-size: 24px;
            letter-spacing: 7px;
            font-family: 'Ciutadella regular';
        }

        .greetings {
            position: absolute;
            left: 44%;
            top: 37%;
            font-weight: 500;
            font-size: 20px;
            font-family: 'Ciutadella semibold';
        }

        .username {
            position: absolute;
            left: 24%;
            top: 41%;
            font-family: 'Jacksilver';
            letter-spacing: 2px;
            font-size: 80px;
            color: #f89e1e;
        }

        .description {
            position: absolute;
            width: 1070px;
            text-align: center;
            left: 4%;
            top: 56%;
            font-size: 24px;
            font-family: 'Ciutadella regular';
        }

        .notifs {
            position: absolute;
            width: 980px;
            text-align: center;
            left: 11%;
            top: 69%;
            font-weight: 600;
            font-size: 22px;
            font-family: 'Ciutadella semibold';
        }

        .date {
            position: absolute;
            /* font-style: italic; */
            right: 10%;
            top: 75%;
            font-size: 18px;
            font-family: 'Ciutadella light italic';
        }

        .sign2 {
            position: absolute;
            /* font-style: italic; */
            right: 13%;
            top: 80%;
            font-size: 18px;
            font-family: 'Ciutadella light italic';
        }

        .sign1 {
            position: absolute;
            /* font-style: italic; */
            left: 19%;
            top: 80%;
            font-size: 18px;
            font-family: 'Ciutadella light italic';
        }

        .qr-code {
            position: absolute;
            text-align: center;
            padding: 2px;
            left: 4%;
            top: 87%;
            font-size: 12px;
            font-weight: bold;
            height: 85px;
            border-radius: 3px;
            width: 70px;
            background-color: #f89e1e;
        }

        .qr-code div:first-child {
            height: 60px;
            margin: auto auto 5px;
            padding: 2px;
            border-radius: 3px;
            background: white;
            width: 65px;
        }

        .barre-code {
            position: absolute;
            /* height: 130px; */
            background: white;
            /* width: 15px; */
            right: -70px;
            top: 55%;
            transform: rotate(90deg);
        }

        .bold {
            font-family: 'Ciutadella bold';
        }
        .bold-italic {
            font-family: 'Ciutadella bold italic';
        }

        .light-italic {
            font-family: 'Ciutadella light italic';
        }
    </style>
</head>

<body>
    <div class="main">


        <img src="{{ asset('assets/images/logo.png') }}" alt="logo" id="logo">

        <h1 class="title">CERTIFICAT</h1>

        <div class="sub-title">DE FORMATION</div>

        <div class="greetings">Félicitations à</div>

        <div class="username">Jean Marcel KONAN</div>

        <div class="description">Vous avez effectué avec succès du <b class="bold">05 au 10 Août 2013, 45 heures</b> de formation
            pratique sur le thème
            <b class="bold">Formation PMP – Project Management Professional</b> <em class="light-italic">[Formation avec préparation à la certification
                PMP® : Project
                Management Professional]</em>.
        </div>

        <div class="notifs">En foi de quoi, ce certificat vous est délivré, pour servir et valoir ce que de droit.
        </div>

        <div class="date">Fait <u>à <b class="bold-italic">Abidjan</b>, le <b class="bold-italic">15 Août 2</u>013</b></div>

        <div class="sign2">Président du Comité de suivi pédagogique
            <br><b class="bold-italic">Ludovic KOUADIO</b>
            <div>
                <img src="https://api.neurones-academy.com/assets/right-signature.png" height="100" width="160" alt="qr-code" id="qr-code">
            </div>
        </div>

        <div class="sign1">Président du Directoire Salomon Betsaleel Business School (SBBS)
            <br><b class="bold-italic">Jean-Marcel KONAN</b>
            <div>
                <img src="https://api.neurones-academy.com/assets/left-signature.png" height="100" width="160" alt="qr-code" id="qr-code">
            </div>
        </div>

        <div class="qr-code">
            <div>
                {{-- <img src="https://api.neurones-academy.com/assets/qr-code.png" height="60" width="60" alt="qr-code" id="qr-code"> --}}
            </div>
            <div>SCAN ME</div>
        </div>

        <div class="barre-code">
            <div style="font-size:0;position:relative;width:190px;height:10px;">
<div style="background-color:black;width:2px;height:10px;position:absolute;left:0px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:4px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:12px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:18px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:26px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:32px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:40px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:46px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:54px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:60px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:68px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:74px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:82px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:88px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:92px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:96px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:6px;height:10px;position:absolute;left:100px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:110px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:6px;height:10px;position:absolute;left:114px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:124px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:6px;height:10px;position:absolute;left:128px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:138px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:6px;height:10px;position:absolute;left:142px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:152px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:156px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:4px;height:10px;position:absolute;left:164px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:170px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:178px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:184px;top:0px;">&nbsp;</div>
<div style="background-color:black;width:2px;height:10px;position:absolute;left:188px;top:0px;">&nbsp;</div>
</div>

        </div>
    </div>
</body>

</html>
