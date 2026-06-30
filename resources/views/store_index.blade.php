<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vani Voice Chat</title>
    <link rel="apple-touch-icon" sizes="180x180" href="https://vanivoicechat.kotiboxglobaltech.online/storage/application/1772193435_5538.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://vanivoicechat.kotiboxglobaltech.online/storage/application/1772193435_5538.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://vanivoicechat.kotiboxglobaltech.online/storage/application/1772193435_5538.png">
    <link rel="shortcut icon" type="image/x-icon" href="https://vanivoicechat.kotiboxglobaltech.online/storage/application/1772193435_5538.png">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet" />
    <style>
        .logo img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }
        .logo {
            display: flex;
            align-items: center;
        }

        .real-screen {
            width: 190px;
            height: 360px;
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(124, 34, 232, .22);
            background: #0d0520;
            transition: transform .3s, box-shadow .3s;
        }

        .real-screen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .real-screen:hover {
            transform: translateY(-10px) scale(1.04);
            box-shadow: 0 30px 60px rgba(124, 34, 232, .25);
        }

        .real-screen:nth-child(2) {
            margin-top: -20px;
        }

        .real-screen:nth-child(3) {
            margin-top: 10px;
        }



        :root {
            --purple: #7C22E8;
            --purple2: #9B3FF5;
            --violet: #5B0FBF;
            --deep: #0D0520;
            --mid: #160A32;
            --card: #1C0D3A;
            --gold: #F5C842;
            --gold2: #E8A020;
            --pink: #E8409A;
            --border: rgba(124, 34, 232, .22);
            --txt: #EDE8FF;
            --muted: #8878AA;
            --white: #ffffff;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--deep);
            color: var(--txt);
            font-family: 'DM Sans', sans-serif;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
            background-size: 200px 200px;
            pointer-events: none;
            opacity: .5;
        }

        /* NAV */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 6vw;
            backdrop-filter: blur(20px);
            background: rgba(13, 5, 32, .75);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.7rem;
            letter-spacing: -.02em;
            background: linear-gradient(120deg, var(--gold), var(--purple2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: color .2s;
        }

        .nav-links a:hover {
            color: var(--gold);
        }

        .nav-cta {
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            color: #1a0a00;
            border: none;
            border-radius: 50px;
            padding: .55rem 1.4rem;
            font-size: .88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .2s, transform .2s;
            box-shadow: 0 4px 20px rgba(245, 200, 66, .3);
        }

        .nav-cta:hover {
            opacity: .88;
            transform: translateY(-1px);
        }

        /* HERO */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 6vw 80px;
            gap: 4rem;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse 60% 70% at 10% 40%, rgba(124, 34, 232, .35) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 80% 20%, rgba(232, 64, 154, .2) 0%, transparent 55%),
                radial-gradient(ellipse 40% 50% at 60% 80%, rgba(91, 15, 191, .3) 0%, transparent 55%);
        }

        .stars {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--gold);
            border-radius: 50%;
            animation: twinkle var(--d, 3s) var(--delay, 0s) ease-in-out infinite;
        }

        @keyframes twinkle {

            0%,
            100% {
                opacity: .1;
                transform: scale(1)
            }

            50% {
                opacity: .8;
                transform: scale(1.6)
            }
        }

        .hero-text {
            flex: 1;
            max-width: 560px;
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(245, 200, 66, .1);
            border: 1px solid rgba(245, 200, 66, .25);
            border-radius: 50px;
            padding: .35rem .9rem;
            font-size: .78rem;
            font-weight: 600;
            color: var(--gold);
            margin-bottom: 1.4rem;
            animation: fadeUp .7s ease both;
        }

        .hero-badge span {
            width: 7px;
            height: 7px;
            background: var(--gold);
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1)
            }

            50% {
                opacity: .4;
                transform: scale(1.5)
            }
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.6rem, 5.5vw, 4.4rem);
            font-weight: 800;
            line-height: 1.07;
            letter-spacing: -.03em;
            animation: fadeUp .8s .1s ease both;
        }

        h1 em {
            font-style: normal;
            background: linear-gradient(100deg, var(--gold), #ff9ef5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-sub {
            margin-top: 1.2rem;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
            max-width: 420px;
            animation: fadeUp .8s .2s ease both;
        }

        .btn-row {
            margin-top: 2.2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            animation: fadeUp .8s .3s ease both;
        }

        .store-btn {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .75rem 1.4rem;
            color: var(--white);
            text-decoration: none;
            transition: background .2s, transform .2s, border-color .2s, box-shadow .2s;
        }

        .store-btn:hover {
            background: rgba(124, 34, 232, .25);
            border-color: var(--purple2);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 34, 232, .25);
        }

        .store-btn-text {
            display: flex;
            flex-direction: column;
        }

        .store-btn-text small {
            font-size: .68rem;
            color: var(--muted);
        }

        .store-btn-text strong {
            font-size: .95rem;
            font-weight: 600;
        }

        /* PHONE */
        .hero-visual {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            animation: fadeUp .9s .2s ease both;
        }

        .phone-container {
            position: relative;
            animation: levitate 5s ease-in-out infinite;
        }

        @keyframes levitate {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-18px)
            }
        }

        .phone-glow-ring {
            position: absolute;
            inset: -30px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(124, 34, 232, .45) 0%, transparent 65%);
            filter: blur(35px);
            pointer-events: none;
            animation: glowPulse 3s ease-in-out infinite;
        }

        @keyframes glowPulse {

            0%,
            100% {
                opacity: .6
            }

            50% {
                opacity: 1
            }
        }

        .phone-img {
            width: 300px;
            max-width: 100%;
            display: block;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 30px 70px rgba(124, 34, 232, .55)) drop-shadow(0 0 40px rgba(245, 200, 66, .18));
        }

        .float-badge {
            position: absolute;
            z-index: 10;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .6rem .9rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .75rem;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
            white-space: nowrap;
        }

        .fb-left {
            left: -70px;
            top: 28%;
            animation: fbFloat1 4s ease-in-out infinite;
        }

        .fb-right {
            right: -70px;
            bottom: 28%;
            animation: fbFloat2 4.5s ease-in-out infinite;
        }

        @keyframes fbFloat1 {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-8px)
            }
        }

        @keyframes fbFloat2 {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(8px)
            }
        }

        .fb-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* STATS */
        .stats {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            background: rgba(22, 10, 50, .7);
            backdrop-filter: blur(10px);
        }

        .stat-item {
            flex: 1;
            max-width: 220px;
            text-align: center;
            padding: 2rem 1rem;
            border-right: 1px solid var(--border);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(120deg, var(--gold), var(--purple2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: .82rem;
            color: var(--muted);
            margin-top: .3rem;
        }

        /* FEATURES */
        .features {
            position: relative;
            z-index: 1;
            padding: 100px 6vw;
        }

        .section-label {
            display: inline-block;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: .8rem;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.02em;
            max-width: 480px;
        }

        .section-title em {
            font-style: normal;
            color: var(--gold);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 3.5rem;
        }

        .feat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2rem;
            transition: transform .25s, border-color .25s, box-shadow .25s;
            position: relative;
            overflow: hidden;
        }

        .feat-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(124, 34, 232, .08) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .3s;
        }

        .feat-card:hover {
            transform: translateY(-6px);
            border-color: rgba(245, 200, 66, .4);
            box-shadow: 0 20px 50px rgba(124, 34, 232, .2);
        }

        .feat-card:hover::after {
            opacity: 1;
        }

        .feat-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
            background: linear-gradient(135deg, rgba(124, 34, 232, .2), rgba(245, 200, 66, .1));
            border: 1px solid rgba(245, 200, 66, .2);
        }

        .feat-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: .6rem;
        }

        .feat-desc {
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.65;
        }

        /* PARTY SECTION */
        .party-section {
            position: relative;
            z-index: 1;
            padding: 80px 6vw;
            display: flex;
            align-items: center;
            gap: 5rem;
            overflow: hidden;
        }

        .party-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(ellipse 70% 80% at 50% 50%, rgba(124, 34, 232, .1) 0%, transparent 70%);
        }

        .party-content {
            flex: 1;
            max-width: 480px;
        }

        .party-content p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            margin-top: 1rem;
        }

        .party-visual {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .party-cards {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 320px;
            width: 100%;
        }

        .p-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.2rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform .2s, border-color .2s;
        }

        .p-card:hover {
            transform: translateX(8px);
            border-color: rgba(245, 200, 66, .3);
        }

        .p-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .p-card-icon.gold {
            background: rgba(245, 200, 66, .15);
        }

        .p-card-icon.pink {
            background: rgba(232, 64, 154, .15);
        }

        .p-card-icon.purple {
            background: rgba(124, 34, 232, .2);
        }

        .p-card-title {
            font-size: .9rem;
            font-weight: 600;
            color: var(--txt);
        }

        .p-card-sub {
            font-size: .75rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .live-chip {
            margin-left: auto;
            font-size: .62rem;
            font-weight: 700;
            background: rgba(232, 64, 154, .15);
            color: var(--pink);
            border: 1px solid rgba(232, 64, 154, .25);
            border-radius: 50px;
            padding: 3px 9px;
            flex-shrink: 0;
        }

        /* SCREENSHOTS */
        .screenshots {
            position: relative;
            z-index: 1;
            padding: 80px 6vw;
            text-align: center;
        }

        .screens-track {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 3rem;
        }

        .mock-screen {
            width: 190px;
            height: 360px;
            background: linear-gradient(160deg, #1e0a40, #0d0520);
            border: 1px solid var(--border);
            border-radius: 30px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            transition: transform .3s, box-shadow .3s;
        }

        .mock-screen:hover {
            transform: translateY(-10px) scale(1.04);
            box-shadow: 0 30px 60px rgba(124, 34, 232, .25);
        }

        .mock-screen:nth-child(2) {
            margin-top: -20px;
        }

        .mock-screen:nth-child(3) {
            margin-top: 10px;
        }

        .mock-top {
            height: 24px;
            background: rgba(124, 34, 232, .1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mock-notch {
            width: 55px;
            height: 12px;
            background: #0d0520;
            border-radius: 10px;
        }

        .mock-body {
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .mock-bar {
            height: 9px;
            border-radius: 6px;
            background: rgba(124, 34, 232, .15);
        }

        .mock-bar.gold {
            background: linear-gradient(90deg, var(--gold), var(--gold2));
            width: 55%;
        }

        .mock-bar.purple {
            background: linear-gradient(90deg, var(--purple), var(--purple2));
            width: 75%;
        }

        .mock-avatar-row {
            display: flex;
            gap: 5px;
            margin: 4px 0;
        }

        .mock-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .mock-avatar.a {
            background: linear-gradient(135deg, var(--purple), var(--pink));
        }

        .mock-avatar.b {
            background: linear-gradient(135deg, var(--gold), #ff7730);
        }

        .mock-avatar.c {
            background: linear-gradient(135deg, #00d4aa, #0078ff);
        }

        .mock-avatar.d {
            background: linear-gradient(135deg, #ff4488, #9b3ff5);
        }

        .mock-msg {
            background: rgba(124, 34, 232, .12);
            border-radius: 9px;
            height: 26px;
            width: 80%;
        }

        .mock-msg.right {
            align-self: flex-end;
            background: rgba(245, 200, 66, .12);
        }

        .mock-card-img {
            height: 70px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(124, 34, 232, .3), rgba(232, 64, 154, .2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .mock-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 4px;
        }

        .mock-tab {
            flex: 1;
            height: 20px;
            border-radius: 8px;
            background: rgba(124, 34, 232, .12);
        }

        .mock-tab.active {
            background: linear-gradient(90deg, var(--purple), var(--purple2));
        }

        .mock-wave-bar {
            width: 3px;
            border-radius: 3px;
            background: linear-gradient(to top, var(--gold), var(--purple2));
        }

        /* CTA */
        .cta-section {
            position: relative;
            z-index: 1;
            padding: 100px 6vw;
            text-align: center;
            overflow: hidden;
        }

        .cta-bg {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 50%, rgba(124, 34, 232, .12) 0%, transparent 70%);
            pointer-events: none;
        }

        .cta-box {
            position: relative;
            max-width: 680px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 4rem 3rem;
        }

        .cta-box::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 32px;
            background: linear-gradient(135deg, rgba(124, 34, 232, .08), rgba(245, 200, 66, .04));
            pointer-events: none;
        }

        .cta-box h2 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.02em;
            margin-bottom: 1rem;
        }

        .cta-box h2 em {
            font-style: normal;
            color: var(--gold);
        }

        .cta-box p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
            max-width: 460px;
            margin: 0 auto 2.2rem;
        }

        .cta-btns {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* FOOTER */
        footer {
            position: relative;
            z-index: 1;
            border-top: 1px solid var(--border);
            padding: 2.5rem 6vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background: rgba(13, 5, 32, .9);
        }

        .footer-logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            background: linear-gradient(120deg, var(--gold), var(--purple2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: .85rem;
            transition: color .2s;
        }

        .footer-links a:hover {
            color: var(--gold);
        }

        .footer-copy {
            color: var(--muted);
            font-size: .8rem;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(24px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        @keyframes wave {
            from {
                height: 4px
            }

            to {
                height: var(--h, 16px)
            }
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity .7s ease, transform .7s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media(max-width:900px) {
            .party-section {
                flex-direction: column;
            }

            .party-visual {
                width: 100%;
            }
        }

        @media(max-width:768px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding-top: 100px;
            }

            .hero-sub {
                margin: 1rem auto 0;
            }

            .btn-row {
                justify-content: center;
            }

            .hero-visual {
                order: -1;
            }

            .phone-img {
                width: 240px;
            }

            .fb-left,
            .fb-right {
                display: none;
            }

            nav .nav-links {
                display: none;
            }

            .stat-item {
                padding: 1.5rem .5rem;
            }

            .stat-num {
                font-size: 1.6rem;
            }

            footer {
                flex-direction: column;
                text-align: center;
            }

            .footer-links {
                justify-content: center;
            }

            .cta-box {
                padding: 2.5rem 1.5rem;
            }
        }
    </style>
</head>

<body>

    <nav>
        <div class="logo"><img src="{{ asset('assets/vani_three.png') }}" alt="Vani Logo"></div>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#party">Party</a></li>
            <li><a href="#download">Download</a></li>
        </ul>
        <a href="#download" class="nav-cta">Get the App</a>
    </nav>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="stars" id="stars"></div>

        <div class="hero-text">
            <div class="hero-badge"><span></span> Party rooms going live now</div>
            <h1>Chat. Party.<br /><em>Meet the World.</em></h1>
            <p class="hero-sub">Join live voice parties, meet new friends globally, and experience real conversations — powered by the energy of your voice.</p>
            <div class="btn-row">
                <a href="#" class="store-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z" />
                    </svg>
                    <div class="store-btn-text"><small>Download on the</small><strong>App Store</strong></div>
                </a>
                <a href="#" class="store-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.18 23.76c.3.17.64.2.96.09l.1-.06 11.5-6.54-2.49-2.51-10.07 9.02zm14.6-8.3L15.4 18l2.38 2.4 2.83-1.61c.8-.46.8-1.68 0-2.14l-2.83-1.19zM3.04 1.07C2.7 1.38 2.5 1.86 2.5 2.47v19.06c0 .61.2 1.09.54 1.4l.07.06 10.67-10.7v-.25L3.11 1.01l-.07.06zm11.23 10.28L12 9.08 3.18.24c-.32-.11-.66-.08-.96.09l10.09 11.02z" />
                    </svg>
                    <div class="store-btn-text"><small>Get it on</small><strong>Google Play</strong></div>
                </a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="float-badge fb-left">
                <div class="fb-dot" style="background:#F5C842"></div>
                🎉 128 parties live
            </div>
            <div class="float-badge fb-right">
                <div class="fb-dot" style="background:#E8409A"></div>
                🔥 2M+ users online
            </div>
            <div class="phone-container">
                <div class="phone-glow-ring"></div>
                <img src="assets/phone.png" alt="Vani App" class="phone-img" />
            </div>
        </div>
    </section>

    <div class="stats reveal">
        <div class="stat-item">
            <div class="stat-num">2M+</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">50K+</div>
            <div class="stat-label">Daily Rooms</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">180+</div>
            <div class="stat-label">Countries</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">4.8★</div>
            <div class="stat-label">App Rating</div>
        </div>
    </div>

    <section class="features" id="features">
        <div class="reveal">
            <div class="section-label">Why Vani Voice Chat</div>
            <h2 class="section-title">Everything you need to <em>connect & party</em></h2>
        </div>
        <div class="features-grid">
            <div class="feat-card reveal">
                <div class="feat-icon">🎙</div>
                <div class="feat-title">Live Voice Rooms</div>
                <p class="feat-desc">Create or join live voice rooms — public or private. Talk with friends or meet new faces who share your vibe, in real time.</p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon">🎉</div>
                <div class="feat-title">Party Mode</div>
                <p class="feat-desc">Host wild themed parties or join ongoing ones. Invite your crew, pick a vibe, and turn any moment into a celebration.</p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon">📡</div>
                <div class="feat-title">Broadcast Live</div>
                <p class="feat-desc">Go broadcast and share your voice with the world. Build your audience and become a voice influencer on Vani.</p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon">💬</div>
                <div class="feat-title">One-on-One Calls</div>
                <p class="feat-desc">Connect deeper with private voice calls. Free from the crowd — share your story, your laughter, your moments.</p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon">🌐</div>
                <div class="feat-title">Global Community</div>
                <p class="feat-desc">Connect across 180+ countries. Language barriers fade when you're speaking from the heart with real people worldwide.</p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon">🛡</div>
                <div class="feat-title">Safe & Moderated</div>
                <p class="feat-desc">Smart moderation keeps Vani positive. Block, report, and stay in control — your safety is our priority.</p>
            </div>
        </div>
    </section>

    <section class="party-section" id="party">
        <div class="party-bg"></div>
        <div class="party-content reveal">
            <div class="section-label">Live Right Now</div>
            <h2 class="section-title">Join a <em>party</em> in seconds</h2>
            <p>Hot rooms, new rooms, themed parties — there's always something happening on Vani. Just tap and dive in. Your next conversation is one click away.</p>
        </div>
        <div class="party-visual reveal">
            <div class="party-cards">
                <div class="p-card">
                    <div class="p-card-icon gold">🎵</div>
                    <div>
                        <div class="p-card-title">Chill Vibes & Music</div>
                        <div class="p-card-sub">🎙 128 listeners</div>
                    </div>
                    <div class="live-chip">LIVE</div>
                </div>
                <div class="p-card">
                    <div class="p-card-icon pink">🌍</div>
                    <div>
                        <div class="p-card-title">Language Exchange</div>
                        <div class="p-card-sub">🎙 64 listeners</div>
                    </div>
                    <div class="live-chip">LIVE</div>
                </div>
                <div class="p-card">
                    <div class="p-card-icon purple">✨</div>
                    <div>
                        <div class="p-card-title">Late Night Party</div>
                        <div class="p-card-sub">🎙 312 listeners</div>
                    </div>
                    <div class="live-chip">LIVE</div>
                </div>
                <div class="p-card">
                    <div class="p-card-icon gold">🏆</div>
                    <div>
                        <div class="p-card-title">Ranking Battles</div>
                        <div class="p-card-sub">🎙 89 listeners</div>
                    </div>
                    <div class="live-chip">LIVE</div>
                </div>
            </div>
        </div>
    </section>

    <section class="screenshots" id="screenshots">
        <div class="reveal">
            <div class="section-label">The App</div>
            <h2 class="section-title" style="margin:0 auto;text-align:center;">Crafted for <em>your voice</em></h2>
        </div>

        <div class="screens-track">

            <div class="real-screen reveal">
                <img src="{{ asset('assets/phone 1.png') }}" alt="App Screen 1" />
            </div>

            <div class="real-screen reveal">
                <img src="{{ asset('assets/phone 2.png') }}" alt="App Screen 2" />
            </div>

            <div class="real-screen reveal">
                <img src="{{ asset('assets/phone 3.png') }}" alt="App Screen 3" />
            </div>

        </div>
    </section>

    <section class="cta-section" id="download">
        <div class="cta-bg"></div>
        <div class="cta-box reveal">
            <h2>Your voice deserves<br />to <em>party</em></h2>
            <p>Download Vani for free and join millions of people around the world connecting through live voice, parties, and real conversations.</p>
            <div class="cta-btns">
                <a href="#" class="store-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z" />
                    </svg>
                    <div class="store-btn-text"><small>Download on the</small><strong>App Store</strong></div>
                </a>
                <a href="#" class="store-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.18 23.76c.3.17.64.2.96.09l.1-.06 11.5-6.54-2.49-2.51-10.07 9.02zm14.6-8.3L15.4 18l2.38 2.4 2.83-1.61c.8-.46.8-1.68 0-2.14l-2.83-1.19zM3.04 1.07C2.7 1.38 2.5 1.86 2.5 2.47v19.06c0 .61.2 1.09.54 1.4l.07.06 10.67-10.7v-.25L3.11 1.01l-.07.06zm11.23 10.28L12 9.08 3.18.24c-.32-.11-.66-.08-.96.09l10.09 11.02z" />
                    </svg>
                    <div class="store-btn-text"><small>Get it on</small><strong>Google Play</strong></div>
                </a>
            </div>
        </div>
    </section>

    <footer>
        <div class="logo"><img src="{{ asset('assets/vani_three.png') }}" alt="Vani Logo">Vani Voice Chat</div>
        <div class="footer-links">
            <a href="#">Terms of Service</a>
            <a href="#">Privacy Policy</a>
            <a href="mailto:hello@vaniapp.com">Contact</a>
        </div>
        <div class="footer-copy">© 2024 Vani Voice Chat. All rights reserved.</div>
    </footer>

    <script>
        // Twinkling stars
        const sc = document.getElementById('stars');
        const colors = ['#F5C842', '#9B3FF5', '#E8409A', '#ffffff'];
        for (let i = 0; i < 70; i++) {
            const s = document.createElement('div');
            s.className = 'star';
            s.style.cssText = `left:${Math.random()*100}%;top:${Math.random()*100}%;--d:${2+Math.random()*3}s;--delay:${Math.random()*3}s;width:${1+Math.random()*2.5}px;height:${1+Math.random()*2.5}px;background:${colors[Math.floor(Math.random()*colors.length)]};`;
            sc.appendChild(s);
        }
        // Scroll reveal
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) setTimeout(() => e.target.classList.add('visible'), i * 60);
            });
        }, {
            threshold: 0.12
        });
        document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
    </script>
</body>

</html>