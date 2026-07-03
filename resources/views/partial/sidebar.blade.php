<nav id="sidebar" style="background-color: #5d3eb1cf;">
    <div class="shadow-bottom"></div>
    <ul class="list-unstyled menu-categories ps ps--active-y" id="accordionExample">
        <li class="menu @routeis('dashboard') active @endrouteis">
            <a href="{{ route('dashboard') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-duotone fa-house"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>

        <!-- @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('blocking-device') active @endrouteis">
            <a href="#login" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('blocking-device') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Login Manage</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('blocking-device') show @endrouteis" id="login">
                @if(Helper::userCan(107))
                <li class="@routeis('blocking-device') active @endrouteis">
                    <a href="{{ route('blocking-device') }}">Blocking Manage</a>
                </li>
                @endif
            </ul>
        </li>
        @endif -->


        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('app-users,user.albums,user.items') active @endrouteis">
            <a href="#users" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('app-users,user.albums,user.items') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-users"></i>
                    <span>User App</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('app-users,user.albums,user.items') show @endrouteis" id="users">

                @if(Helper::userCan(107))
                <li class="@routeis('app-users') active @endrouteis">
                    <a href="{{ route('app-users') }}">User List</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('user.albums') active @endrouteis">
                    <a href="{{ route('user.albums') }}">User Album</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('user.items') active @endrouteis">
                    <a href="{{ route('user.items') }}">User Own Items</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('reward-invitation-recharge,reward-inviting') active @endrouteis">
            <a href="#invitationmanage" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('reward-invitation-recharge,reward-inviting') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Invitation Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('reward-invitation-recharge,reward-inviting') show @endrouteis" id="invitationmanage">

                <!-- @if(Helper::userCan(107))
                <li class="@routeis('reward-invitation-recharge') active @endrouteis">
                    <a href="{{ route('reward-invitation-recharge') }}">Reward Recharge</a>
                </li>
                @endif -->

                @if(Helper::userCan(107))
                <li class="@routeis('reward-inviting') active @endrouteis">
                    <a href="{{ route('reward-inviting') }}">Reward Invitation</a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('giftrecords') active @endrouteis">
            <a href="#oprationLog" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('giftrecords') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Opration Log</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('giftrecords') show @endrouteis" id="oprationLog">

                @if(Helper::userCan(107))
                <li class="@routeis('giftrecords') active @endrouteis">
                    <a href="{{ route('giftrecords') }}">Gift Flow</a>
                </li>
                @endif
            </ul>
        </li>
        @endif


        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('banner,premium_number,treasure-levels.index,treasure-level-rewards') active @endrouteis">
            <a href="#dataconfig" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('banner,premium_number,treasure-levels.index,treasure-level-rewards') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-database"></i>

                    <span>Data Config</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('banner,premium_number,treasure-levels.index,treasure-level-rewards') show @endrouteis" id="dataconfig">

                @if(Helper::userCan(107))
                <li class="@routeis('banner') active @endrouteis">
                    <a href="{{ route('banner') }}">Banner Management</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('premium_number') active @endrouteis">
                    <a href="{{ route('premium_number') }}">Premium Number Management</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('treasure-levels.index') active @endrouteis">
                    <a href="{{ route('treasure-levels.index') }}">Treasure</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('treasure-level-rewards') active @endrouteis">
                    <a href="{{ route('treasure-level-rewards') }}">
                        Treasure Level Rewards
                    </a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('gift,frame,theme,cars,chat.bubble,data.card,entry.tag,voice,store.uid,vip,svip,medals.index,props.item.delivery') active @endrouteis">
            <a href="#props" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('gift,frame,theme,cars,chat.bubble,data.card,entry.tag,voice,store.uid,vip,svip,medals.index,props.item.delivery') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-motorcycle"></i>

                    <span>Props Manage</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('gift,frame,theme,cars,chat.bubble,data.card,entry.tag,voice,store.uid,vip,svip,medals.index,props.item.delivery') show @endrouteis" id="props">

                @if(Helper::userCan(107))
                <li class="@routeis('gift') active @endrouteis">
                    <a href="{{ route('gift') }}">Gifts</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('frame') active @endrouteis">
                    <a href="{{ route('frame') }}">Frame</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('theme') active @endrouteis">
                    <a href="{{ route('theme') }}">Theme</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('cars') active @endrouteis">
                    <a href="{{ route('cars') }}">Entry</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('chat.bubble') active @endrouteis">
                    <a href="{{ route('chat.bubble') }}">Chat Bubble</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('data.card') active @endrouteis">
                    <a href="{{ route('data.card') }}">Profile Card</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('entry.tag') active @endrouteis">
                    <a href="{{ route('entry.tag') }}">Entry Tag</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('voice') active @endrouteis">
                    <a href="{{ route('voice') }}">Voice</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('store.uid') active @endrouteis">
                    <a href="{{ route('store.uid') }}">Id</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('vip') active @endrouteis">
                    <a href="{{ route('vip') }}">VIP</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('svip') active @endrouteis">
                    <a href="{{ route('svip') }}">SVIP</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('medals.index') active @endrouteis">
                    <a href="{{ route('medals.index') }}">Medals</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('props.item.delivery') active @endrouteis">
                    <a href="{{ route('props.item.delivery') }}">Item delivery</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('relationship.item,relationship.user.relation.list') active @endrouteis">
            <a href="#relationship" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('relationship.item,relationship.user.relation.list') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-handshake"></i>

                    <span>Relationship Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('relationship.item,relationship.user.relation.list') show @endrouteis" id="relationship">

                @if(Helper::userCan(107))
                <li class="@routeis('relationship.item') active @endrouteis">
                    <a href="{{ route('relationship.item') }}">Relationship Items List</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('relationship.user.relation.list') active @endrouteis">
                    <a href="{{ route('relationship.user.relation.list') }}">User Relation List</a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('room,room_reward_slabs,room_reward_claims,red.envelope,user_room.music,user.themes') active @endrouteis">
            <a href="#room" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('room,room_reward_slabs,room_reward_claims,red.envelope,user_room.music,user.themes') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-door-open"></i>

                    <span>Room Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('room,room_reward_slabs,room_reward_claims,red.envelope,user_room.music,user.themes') show @endrouteis" id="room">

                @if(Helper::userCan(107))
                <li class="@routeis('room') active @endrouteis">
                    <a href="{{ route('room') }}">Room List</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('room_reward_slabs') active @endrouteis">
                    <a href="{{ route('room_reward_slabs') }}">Room Reward Slab </a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('room_reward_claims') active @endrouteis">
                    <a href="{{ route('room_reward_claims') }}">Room Reward Claims </a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('red.envelope') active @endrouteis">
                    <a href="{{ route('red.envelope') }}">Red Envelope </a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('user_room.music') active @endrouteis">
                    <a href="{{ route('user_room.music') }}">User Music list </a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('user.themes') active @endrouteis">
                    <a href="{{ route('user.themes') }}">User Active Themes </a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <!-- <li class="menu @routeis('agency,admin.account,bd-user,host') active @endrouteis">
            <a href="#adminAccount" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('agency,admin.account,bd-user,host') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-microphone"></i>

                    <span>Anchor Center</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('agency,admin.account') show @endrouteis" id="adminAccount">

                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('admin.account') }}">Admin</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('agency') }}">Agency</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('bd-user') }}">BD</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('host') }}">Hosts</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('coin_seller') }}">Coin Seller</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('merchant') }}">Merchant</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="">
                    <a href="{{ route('coin_seller.transactions') }}">Sellers Recharge History</a>
                </li>
                @endif
            </ul>
        </li> -->
        <li class="menu {{ request()->routeIs('agency*','admin.account*','bd-user*','host*','coin_seller','merchant','coin_seller.transactions','coin-conversion-rate') ? 'active' : '' }}">

            <a href="#adminAccount"
                data-bs-toggle="collapse"
                aria-expanded="{{ request()->routeIs('agency*','admin.account*','bd-user*','host*','coin_seller','merchant','coin_seller.transactions','coin-conversion-rate') ? 'true' : 'false' }}"
                class="dropdown-toggle">

                <div class="">
                    <i class="fa-solid fa-microphone"></i>
                    <span>Anchor Center</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>

            <ul class="collapse submenu list-unstyled {{ request()->routeIs('agency*','admin.account*','bd-user*','host*','coin_seller','merchant','coin_seller.transactions','coin-conversion-rate') ? 'show' : '' }}"
                id="adminAccount">

                <li class="{{ request()->routeIs('admin.account') ? 'active' : '' }}">
                    <a href="{{ route('admin.account') }}">Admin</a>
                </li>

                <li class="{{ request()->routeIs('bd-user') ? 'active' : '' }}">
                    <a href="{{ route('bd-user') }}">BD</a>
                </li>

                <li class="{{ request()->routeIs('agency') ? 'active' : '' }}">
                    <a href="{{ route('agency') }}">Agency</a>
                </li>

                <li class="{{ request()->routeIs('host') ? 'active' : '' }}">
                    <a href="{{ route('host') }}">Hosts</a>
                </li>

                <li class="{{ request()->routeIs('coin_seller') ? 'active' : '' }}">
                    <a href="{{ route('coin_seller') }}">Coin Seller</a>
                </li>

                <li class="{{ request()->routeIs('merchant') ? 'active' : '' }}">
                    <a href="{{ route('merchant') }}">Merchant</a>
                </li>

                <li class="{{ request()->routeIs('coin_seller.transactions') ? 'active' : '' }}">
                    <a href="{{ route('coin_seller.transactions') }}">Sellers Recharge History</a>
                </li>

                <li class="{{ request()->routeIs('coin-conversion-rate') ? 'active' : '' }}">
                    <a href="{{ route('coin-conversion-rate') }}">Coin Conversion Rate</a>
                </li>

                <li class="{{ request()->routeIs('host-policy') ? 'active' : '' }}">
                    <a href="{{ route('host-policy') }}">Policy</a>
                </li>
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('broadcast-price,broadcast') active @endrouteis">
            <a href="#broadcast" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('broadcast-price,broadcast') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-tower-broadcast"></i>

                    <span>Broadcasting Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('broadcast-price,broadcast') show @endrouteis" id="broadcast">

                @if(Helper::userCan(107))
                <li class="{{ request()->routeIs('broadcast-price') ? 'active' : '' }}">
                    <a href="{{ route('broadcast-price') }}">broadcasting Price List</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="{{ request()->routeIs('broadcast') ? 'active' : '' }}">
                    <a href="{{ route('broadcast') }}">broadcasting List</a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('topic.category,topic,posts.index,user.post.reports') active @endrouteis">
            <a href="#moment" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('topic.category,topic,posts.index,user.post.reports') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-photo-film"></i>

                    <span>Moments Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('topic.category,topic,posts.index,user.post.reports') show @endrouteis" id="moment">

                @if(Helper::userCan(107))
                <li class="@routeis('topic.category') active @endrouteis">
                    <a href="{{ route('topic.category') }}">Topic Category List</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('topic') active @endrouteis">
                    <a href="{{ route('topic') }}">Topic List</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('posts.index') active @endrouteis">
                    <a href="{{ route('posts.index') }}">Posts</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('user.post.reports') active @endrouteis">
                    <a href="{{ route('user.post.reports') }}">Post Reports</a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('levels,user.medals') active @endrouteis">
            <a href="#hierarchical" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('levels,user.medals') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-sitemap"></i>

                    <span>Hierarchical Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('levels,user.medals') show @endrouteis" id="hierarchical">

                @if(Helper::userCan(107))
                <li class="@routeis('levels') active @endrouteis">
                    <a href="{{ route('levels') }}">Level List</a>
                </li>
                @endif
                 
                @if(Helper::userCan(107))
                <li class="@routeis('user.medals') active @endrouteis">
                    <a href="{{ route('user.medals') }}">User Medals</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('family,family.rank') active @endrouteis">
            <a href="#faimlymanage" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('family,family.rank') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-heart"></i>

                    <span>Family Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('family,family.rank') show @endrouteis" id="faimlymanage">

                @if(Helper::userCan(107))
                <li class="@routeis('family') active @endrouteis">
                    <a href="{{ route('family') }}">Family List</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('family.rank') active @endrouteis">
                    <a href="{{ route('family.rank') }}">Family Rank</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('official_notifications.index,customer_support') active @endrouteis">
            <a href="#officialnotification" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('official_notifications.index,customer_support') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-comments"></i>

                    <span>Message Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('official_notifications.index,customer_support') show @endrouteis" id="officialnotification">

                @if(Helper::userCan(107))
                <li class="@routeis('official_notifications.index') active @endrouteis">
                    <a href="{{ route('official_notifications.index') }}">Official Note</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('customer_support') active @endrouteis">
                    <a href="{{ route('customer_support') }}">Contact Management</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('coin.package,settlement-log,manual-transfer.index') active @endrouteis">
            <a href="#financial" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('coin.package,settlement-log,manual-transfer.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-wallet"></i>

                    <span>Financial Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('coin.package,settlement-log,manual-transfer.index') show @endrouteis" id="financial">

                @if(Helper::userCan(107))
                <li class="@routeis('coin.package') active @endrouteis">
                    <a href="{{ route('coin.package') }}">Coin Package</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('settlement-log') active @endrouteis">
                    <a href="{{ route('settlement-log') }}">Salary Auto Credit Log</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('manual-transfer.index') active @endrouteis">
                    <a href="{{ route('manual-transfer.index') }}">Manual Money Transfer</a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('post.report,user.report,app-rules.index') active @endrouteis">
            <a href="#report" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('post.report,user.report,app-rules.index') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-clipboard-list"></i>

                    <span>Report Management</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('post.report,user.report,app-rules.index') show @endrouteis" id="report">

                @if(Helper::userCan(107))
                <li class="@routeis('post.report') active @endrouteis">
                    <a href="{{ route('post.report') }}">Moments Report Lists</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('user.report') active @endrouteis">
                    <a href="{{ route('user.report') }}">User Report Lists</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('app-rules.index') active @endrouteis">
                    <a href="{{ route('app-rules.index') }}">App Rule</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan([107,108,109,110]))
        <li class="menu @routeis('roles,users') active @endrouteis">
            <a href="#access-control" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('roles,users') }}" class="dropdown-toggle">
                <div class="">
                    <i class="fa-solid fa-user-shield"></i>

                    <span>Access Control</span>
                </div>
                <div> <i class="fa-solid fa-chevron-right"></i> </div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('roles,users') show @endrouteis" id="access-control">

                @if(Helper::userCan(107))
                <li class="@routeis('roles') active @endrouteis">
                    <a href="{{ route('roles') }}">Roles</a>
                </li>
                @endif

                @if(Helper::userCan(107))
                <li class="@routeis('users') active @endrouteis">
                    <a href="{{ route('users') }}">Sub Admin</a>
                </li>
                @endif

            </ul>
        </li>
        @endif

        @if(Helper::userCan(101))
        <li class="menu @routeis('static-page') active @endrouteis">
            <a href="{{ route('static-page') }}" class="dropdown-toggle">
                <div class="">
                    <!-- <i class="fa-solid fa-file-lines"></i> -->
                    <i class="fa-duotone fa-globe"></i>
                    <span>Static Page</span>
                </div>
            </a>
        </li>
        @endif

        @if(Helper::userCan(101))
        <li class="menu @routeis('setting') active @endrouteis">
            <a href="#setting" data-bs-toggle="collapse" aria-expanded="{{ Helper::routeis('setting') }}"
                class="dropdown-toggle">
                <div class="">
                    <!-- <i class="fa fa-cog my-auto"></i> -->
                    <i class="fa-solid fa-wrench"></i>
                    <span>App Setting</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled @routeis('setting') show @endrouteis" id="setting">
                @foreach(config('constant.setting_array', []) as $key => $setting)
                <li class="@if(request()->path() == 'setting/'.$key) active @endif">
                    <a class="nav-link" href="{{ route('setting', ['id' => $key]) }}">
                        {{ $setting }}
                    </a>
                </li>
                @endforeach

            </ul>
        </li>

        @endif
    </ul>
</nav>

<style>

</style>