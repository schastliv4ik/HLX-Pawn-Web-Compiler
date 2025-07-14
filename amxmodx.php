#include <amxmodx>
#include <fakemeta>
#include <hamsandwich>
#include <admin>

#define MAX_PLAYERS 32

new bool:g_bPunished[MAX_PLAYERS+1]; // Массив статуса наказания по индексу игрока (userid к clientid)

public plugin_init()
{
    register_plugin("Плагин админ-команд с наказанием (анти-урон)", "1.0", "ChatGPT");

    // Регистрация команд 
    register_concmd("amx_punish", "cmdPunish", "a", 0, "Наказать игрока (анти-урон) - /amx_punish <id>");
    register_concmd("amx_unpunish", "cmdUnPunish", "a", 0, "Снять наказание - /amx_unpunish <id>");
    register_concmd("amx_punish_list", "cmdPunishList", "a", 0, "Показать список наказанных игроков");

    // Стандартные админские команды уже есть в AMX Admin модуле - не повторяем
    // Подписка на события урона
    HookEvent("player_hurt", Event_PlayerHurt, EventHookMode_Post);

    // Или используем хук на FakeMeta (Hamsandwich)
    SDKHook(0, SDKHook_OnTakeDamage);
}

public sdkhook_ontakedamage(victim, &attacker, &inflictor, &damage, &damagetype)
{
    // Проверим, что атакер — игрок и наказан
    if(attacker > 0 && attacker <= MAX_PLAYERS && g_bPunished[attacker])
    {
        // Обнуляем урон
        damage = 0.0;

        // Можно дополнительно убрать попадания - например, заморозить выстрелы. Но пожелано - пули должны лететь нормально.
        return PLUGIN_HANDLED; // стоп обработка урона
    }

    return PLUGIN_CONTINUE;
}

public Event_PlayerHurt(Handle:event, const String:name[], bool:dontBroadcast)
{
    // Здесь можно иметь дополнительный контроль, но sdkhook лучше
}

public cmdPunish(id)
{
    if(!cmd_access(id, ACCESS_ADMIN, 0))
    {
        client_print(id, print_chat, "[Анти-Урон] У вас недостаточно прав.");
        return PLUGIN_HANDLED;
    }

    new target = cmd_Arg(1);

    if(target == 0)
    {
        client_print(id, print_chat, "[Анти-Урон] Использование: /amx_punish <id>");
        return PLUGIN_HANDLED;
    }

    if(!is_user_connected(target))
    {
        client_print(id, print_chat, "[Анти-Урон] Игрок с таким ID не подключен.");
        return PLUGIN_HANDLED;
    }

    if(g_bPunished[target])
    {
        client_print(id, print_chat, "[Анти-Урон] Игрок уже наказан.");
        return PLUGIN_HANDLED;
    }

    g_bPunished[target] = true;

    client_print(id, print_chat, "[Анти-Урон] Игрок %s наказан (анти-урон включен).", get_user_name(target));
    // Отступим от правила, админы видят следующее сообщение, игрок - нет
    for(new i=1; i<=MAX_PLAYERS; i++)
    {
        if(is_user_connected(i) && cmd_access(i, ACCESS_ADMIN, 0))
        {
            client_print(i, print_chat, "[Анти-Урон] %s наказан админом %s.", get_user_name(target), get_user_name(id));
        }
    }

    return PLUGIN_HANDLED;
}

public cmdUnPunish(id)
{
    if(!cmd_access(id, ACCESS_ADMIN, 0))
    {
        client_print(id, print_chat, "[Анти-Урон] У вас недостаточно прав.");
        return PLUGIN_HANDLED;
    }

    new target = cmd_Arg(1);

    if(target == 0)
    {
        client_print(id, print_chat, "[Анти-Урон] Использование: /amx_unpunish <id>");
        return PLUGIN_HANDLED;
    }

    if(!is_user_connected(target))
    {
        client_print(id, print_chat, "[Анти-Урон] Игрок с таким ID не подключен.");
        return PLUGIN_HANDLED;
    }

    if(!g_bPunished[target])
    {
        client_print(id, print_chat, "[Анти-Урон] Игрок не находится под наказанием.");
        return PLUGIN_HANDLED;
    }

    g_bPunished[target] = false;

    client_print(id, print_chat, "[Анти-Урон] Наказание с игрока %s снято.", get_user_name(target));
    for(new i=1; i<=MAX_PLAYERS; i++)
    {
        if(is_user_connected(i) && cmd_access(i, ACCESS_ADMIN, 0))
        {
            client_print(i, print_chat, "[Анти-Урон] Админ %s снял наказание с %s.", get_user_name(id), get_user_name(target));
        }
    }

    return PLUGIN_HANDLED;
}

public cmdPunishList(id)
{
    if(!cmd_access(id, ACCESS_ADMIN, 0))
    {
        client_print(id, print_chat, "[Анти-Урон] У вас недостаточно прав.");
        return PLUGIN_HANDLED;
    }

    new String:list[512] = "";
    new count = 0;

    for(new i=1; i<=MAX_PLAYERS; i++)
    {
        if(g_bPunished[i] && is_user_connected(i))
        {
            if(count > 0)
            {
                strcat(list, ", ", sizeof(list));
            }
            strcat(list, get_user_name(i), sizeof(list));
            count++;
        }
    }

    if(count == 0)
    {
        client_print(id, print_chat, "[Анти-Урон] Список наказанных пуст.");
    }
    else
    {
        client_print(id, print_chat, "[Анти-Урон] Наказанные игроки: %s", list);
    }

    return PLUGIN_HANDLED;
}

public client_disconnect(id)
{
    if(id > 0 && id <= MAX_PLAYERS)
    {
        g_bPunished[id] = false;
    }
}
