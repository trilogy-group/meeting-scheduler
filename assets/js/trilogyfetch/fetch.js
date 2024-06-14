const { google } = require('googleapis');
const fs = require('fs');

// Start of Global Space
const productNames = {
    "2hr_learning"                    : "2hr Learning",
    "mobilogy-de"                       : "Mobilogy Now-DE",
    "mobilogy-fr"                       : "Mobilogy Now-FR",
    accuris                           : "Accuris",
    acorn                             : "Acorn",
    acquisitions_and_integrations     : "Acquisitions & Integrations",
    agemni                            : "Agemni",
    agentek                           : "Agentek",
    alertfind                         : "Alertfind",
    alp                               : "ALP",
    alterpoint                        : "Alterpoint",
    ams_alertfind                     : "Aurea::AMS::Alertfind",
    answerhub                         : "AnswerHub",
    artemis_finland                   : "Artemis Finland",
    aurea_accounts_portal             : "GFI - Accounts Portal",
    aurea_acrm                        : "Aurea::CRM",
    aurea_aem_lyris_hq                : "Aurea::AEM::Lyris HQ",
    aurea_aem_lyris_lm                : "Aurea::AEM::Lyris LM",
    aurea_aes_cis                     : "Aurea::AES::CIS",
    aurea_aes_edi                     : "Aurea::AES::EDI",
    aurea_ais_dcm                     : "Aurea::AIS::DCM",
    aurea_alss                        : "Aurea::ALSS",
    aurea_ams_messaging_solutions     : "Aurea::AMS::EMS",
    aurea_appmanager                  : "GFI - AppManager",
    aurea_aps_aurea_planning_solutions: "Aurea::APS::Aurea Planning Solutions",
    aurea_archiver                    : "GFI - Archiver",
    aurea_ars_gce                     : "Aurea::ARS::GCE",
    aurea_ars_j2b                     : "Aurea::ARS::J2B",
    aurea_ars_magoffice               : "Aurea::ARS::Magoffice",
    aurea_ars_netpos                  : "Aurea::ARS::Netpos",
    aurea_ars_progest                 : "Aurea::ARS::Progest",
    aurea_clearview                   : "GFI - ClearView",
    aurea_endpointsecurity            : "GFI - EndPointSecurity",
    aurea_eventsmanager               : "GFI - Eventsmanager",
    aurea_exinda_network_orchestrator : "GFI - Exinda Network Orchestrator",
    aurea_faxmaker                    : "GFI - FaxMaker",
    aurea_faxmaker_online             : "GFI - FaxMaker Online",
    aurea_integrations_actional       : "Aurea::Monitor",
    aurea_integrations_savvion        : "Aurea::Process",
    aurea_integrations_sonic          : "Aurea::Messenger",
    aurea_kerio_connect               : "GFI - Kerio Connect",
    aurea_kerio_connect_cloud_shared  : "GFI - Kerio Connect Cloud - Shared",
    aurea_kerio_control               : "GFI - Kerio Control",
    aurea_kerio_operator              : "GFI - Kerio Operator",
    aurea_languard                    : "GFI - LanGuard",
    aurea_mailessentials              : "GFI - MailEssentials",
    aurea_mykerio                     : "GFI - MyKerio",
    aurea_webmonitor                  : "GFI - WebMonitor",
    autotrol                          : "Auto-trol",
    avolin_4gov                       : "GoMembers 4gov",
    avolin_computron                  : "Computron",
    avolin_coretrac                   : "CoreTrac",
    avolin_gomembers                  : "GoMembers Enterprise",
    avolin_imi                        : "IMI",
    avolin_knova                      : "Knova",
    avolin_marketfirst                : "MarketFirst",
    avolin_ondemand                   : "GoMembers OnDemand",
    avolin_onyx                       : "Onyx",
    avolin_pivotal                    : "Pivotal",
    avolin_saratoga                   : "Saratoga",
    avolin_supportsoft                : "SupportSoft",
    avolin_tradebeam                  : "TradeBeam",
    avolin_trak                       : "GoMembers Trak",
    avolin_verdiem                    : "Verdiem",
    avolin_vision                     : "Vision",
    beckon                            : "Beckon",
    biznessapps                       : "BiznessApps",
    bonzai_intranet                   : "Bonzai Intranet",
    broadvision                       : "Aurea Portal Management",
    brytercx                          : "BryterCX",
    callstream                        : "CallStream",
    campaign_manager                  : "Campaign Manager",
    centralhr                         : "CentralHR",
    chute                             : "Chute",
    citynumbers                       : "CityNumbers",
    clear                             : "Clear",
    cloud_cfo                         : "Cloud CFO",
    cloud_charging_and_billing        : "Cloud Charging and Billing",
    cloudfix                          : "CloudFix",
    cloudfix_ff_eng                   : "CloudFix FF-Eng",
    corizon                           : "Corizon",
    cpq                               : "Versata CPQ",
    crossover                         : "Crossover",
    crossover_hiring                  : "Crossover Hiring",
    crossover_internal                : "Crossover Internal",
    cs_ai                             : "CSAI",
    central_collections               : "Central Collections",
    central_compliance                : "Central Compliance",
    cs_central_finance                : "Central Finance",
    cs_central_saas                   : "Central SaaS",
    cs_central_vendor_management      : "Central Vendor Management",
    cs_escalation                     : "CS Escalation",
    cs_foundation_courses             : "CS Foundation Courses",
    customersuccess                   : "CustomerSuccess",
    devflows                          : "DevFlows",
    devspaces                         : "DevSpaces",
    dnn                               : "DNN",
    ecora                             : "Ecora",
    edu                               : "EDU",
    edu_alpha_academics               : "Alpha Academics",
    edu_alpha_academics_language      : "Alpha Academics: Language",
    edu_alpha_academics_math          : "Alpha Academics: Math",
    edu_alpha_academics_reading       : "Alpha Academics: Reading",
    edu_alpha_academics_science       : "Alpha Academics: Science",
    edu_alpha_academics_social_science: "Alpha Academics: Social Science",
    edu_alpha_alpha_coachbot          : "Alpha Alpha Coachbot",
    edu_alpha_testing                 : "Alpha Testing",
    educoaching                       : "Coaching",
    edulearning                       : "Learning",
    edutracking                       : "Tracking",
    email_messaging_solutions         : "Email Messaging Solutions",
    engineyard                        : "Engine Yard",
    epm                               : "EPM Live",
    esport                            : "ESport Academy",
    eti                               : "ETI",
    everest                           : "Everest",
    ffm                               : "Field Force Manager",
    finserv                           : "FinServ",
    firm58                            : "Firm58",
    firstrain                         : "FirstRain",
    fogbugz                           : "FogBugz",
    gensym                            : "Gensym",
    geovue                            : "Geovue",
    gpd                               : "ACM",
    gt_school                         : "GT School",
    hand                              : "@Hand",
    iff                               : "IFF",
    infer                             : "Infer",
    infinio                           : "Infinio",
    influitive                        : "Influitive",
    infobright                        : "Infobright DB",
    infopia                           : "Infopia",
    invigorate                        : "Invigorate",
    iris                              : "Iris",
    jigsawme                          : "Jigsaw Interactive",
    jive                              : "Jive",
    jive_cloud                        : "Jive Cloud",
    jive_hop                          : "Jive HOP",
    kandy                             : "Kandy",
    kandy_cpaas                       : "Kandy Cpaas",
    kayako                            : "Kayako",
    kayako_classic                    : "Kayako Classic",
    learn_and_earn                    : "Learn and Earn",
    list_manager                      : "List Manager",
    m_a                               : "M&A",
    metatomix                         : "Metatomix",
    mobilogy                          : "Mobilogy Now",
    mobilogy_es                       : "Mobilogy Now-ES",
    myalerts                          : "MyAlerts",
    newnet                            : "NewNet",
    nextance                          : "Nextance",
    northplains                       : "Northplains [Legacy]",
    northplains_pdct_telescope        : "Northplains - Telescope",
    northplains_pdct_unify            : "Northplains - Unify/OnBrand",
    northplains_pdct_xinet            : "Northplains - Xinet",
    ns8_protect                       : "NS8 Protect",
    nuview                            : "NuView",
    objectstore                       : "ObjectStore",
    olive_software                    : "Olive Software",
    onescm                            : "OneSCM",
    onespot                           : "OneSpot",
    peerapp                           : "PeerApp",
    placeable                         : "Placeable",
    playbooks                         : "Playbooks",
    post_beyond                       : "Post Beyond",
    postwire                          : "Postwire",
    prologic                          : "Prologic",
    prysm                             : "Prysm",
    purchasingnet                     : "PurchasingNet",
    q                                 : "Q",
    quicksilver                       : "QuickSilver",
    ravenflow                         : "Ravenflow",
    response_tek_telco                : "ResponseTek Telco",
    right90                           : "Right90",
    rmsa                              : "RMSA",
    salesbuilder                      : "Salesbuilder",
    scalearc                          : "ScaleArc",
    schoolloop                        : "School Loop",
    securityfirst                     : "SecurityFirst",
    sensage_ap                        : "SenSage AP",
    service_gateway                   : "Service Gateway (Skyvera)",
    skyvera                           : "Skyvera",
    skyvera_analytics                 : "Skyvera Analytics",
    skyvera_monetization              : "Skyvera Monetization and CxM",
    skyvera_network                   : "Skyvera Network 5G & WiFi",
    skyvera_social                    : "Skyvera Social",
    sli_systems                       : "SLI Systems",
    smartform                         : "Smartform Design",
    smartroutines                     : "SmartRoutines",
    sms_masterminds                   : "Bespeak",
    sococo                            : "Sococo",
    sococo5k                          : "Sococo5k",
    stillsecure                       : "StillSecure",
    stratifyd                         : "Stratifyd",
    streetsmart                       : "StreetSmart",
    studylens                         : "StudyLens",
    symphony_commerce                 : "Symphony Commerce",
    synoptos                          : "Synoptos",
    tempo                             : "Tempo",
    tempo_assembly_lines              : "Tempo Assembly Lines",
    tenfold                           : "TenFold",
    think3                            : "Think3",
    totogi_bss                        : "Totogi BSS",
    totogi_marketplace                : "Totogi Marketplace",
    triactive                         : "TriActive",
    vasona_networks                   : "Vasona Networks",
    vendor_management                 : "Vendor Management",
    versata                           : "Versata BRMS",
    volt_delta                        : "Volt Delta",
    volt_delta_germany                : "Volt Delta Germany",
    volt_delta_north_america          : "Volt Delta North America",
    volt_delta_uk                     : "Volt Delta UK"
};

productShortList = {};
agentList = {};





const SERVICE_ACCOUNT_FILE = 'masproject-419222-60938e76ceb4.json';
const credentials = JSON.parse(fs.readFileSync(SERVICE_ACCOUNT_FILE, 'utf8'));
const auth = new google.auth.GoogleAuth({
  credentials,
  scopes: ['https://www.googleapis.com/auth/spreadsheets'],
});
const sheets = google.sheets({ version: 'v4', auth });

//MAIN Google Sheet Read function
const accessGoogleSheet = async (spreadsheetId, range) => {
  try {
    const response = await sheets.spreadsheets.values.get({
      spreadsheetId,
      range,
    });
    const rows = response.data.values;
    return rows;
  } catch (error) {
    console.error('Error accessing Google Sheet:', error);
  }
};


const nameConvert = (name) => {
    let preferredName;
    let parts = name.split(' - ');
    if (parts.length > 1) {
        preferredName = parts[parts.length -1];
    } else {
        parts = parts[0].split("::");  
        if (parts.length > 1) {
            preferredName = parts[parts.length -1];
        } else {
            preferredName = parts[0]
        }
    }
    return preferredName;
};

const getProducts = async function () {
    const wb = '1v9lYDQvkN65S1-_UeE10XTgxWvfTi4Vz2G1qrPuUUw8';
    const range = 'Skill_allocations!C:H';
    const data = await accessGoogleSheet(wb, range);

    //Write a list of products
    const productTags = new Set(); 
    data.forEach(row => {
        if (row[4] !== '#N/A' && row[0] !== "Agent Name") {
        productTags.add(row[3]);
        }
    });
    productTags.forEach(tag => {
        if (productNames[tag] !== undefined) {
            let name = productNames[tag];
            name = nameConvert(name);
            productShortList[tag] = name;
        }
    });


    //Collect a list of active agents
    const agentSet = new Set(); 
    data.forEach(row => {
        if (row[4] !== '#N/A' && row[0] !== "Agent Name") {
            agentSet.add(row[1]);
        }
    });

    agentSet.forEach(agentEmail => {
        agentList[agentEmail] = {}; 
    });

    Object.keys(agentList).forEach(agentEmail => {
        data.forEach(row => {
            if (row[1] === agentEmail) {
                const agentObj = agentList[agentEmail];
                const nameParts = row[0].split(' ');
                const firstName = nameParts[0];
                const lastName = nameParts[nameParts.length-1];
                agentObj['firstName'] = firstName;
                agentObj['lastName'] = lastName;
                agentObj['zdId'] = row[2];
                agentObj['products'] = agentObj['products'] || [];
                agentObj['products'].push(row[3]);
                agentObj['shift'] = row[4];
                agentObj['team'] = row[5];
            }
        });
    });
    const response = {
        'products' : productShortList, 
        'agents' : agentList
    };

    fs.writeFileSync('/opt/homebrew/var/www/meeting-scheduler/assets/js/trilogyfetch/output.json', JSON.stringify(response,null,2));
}


getProducts();
