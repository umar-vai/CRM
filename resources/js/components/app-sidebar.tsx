import { Link, usePage } from '@inertiajs/react';
import {
    ContactRound,
    LayoutDashboard,
    LayoutGrid,
    Settings2,
    UsersRound,
    UserRoundSearch,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import type { Workspace } from '@/types/platform';

const mainNavItems: NavItem[] = [
    { title: 'Workspaces', href: dashboard(), icon: LayoutGrid },
];

export function AppSidebar() {
    const page = usePage();
    const props = page.props as typeof page.props & {
        workspace?: Workspace;
        canConfigure?: boolean;
        canManageTeam?: boolean;
    };
    const workspace = props.workspace;
    const base = workspace ? '/workspaces/' + workspace.id : '';
    const path = page.url.split('?')[0];
    const bn = workspace?.locale === 'bn';
    const workspaceItems = workspace
        ? [
              {
                  title: bn ? 'সারাংশ' : 'Overview',
                  href: base,
                  icon: LayoutDashboard,
              },
              {
                  title: bn ? 'লিড ম্যানেজমেন্ট' : 'Leads',
                  href: base + '/leads',
                  icon: UserRoundSearch,
              },
              {
                  title: bn ? 'কাস্টমার' : 'Customers',
                  href: base + '/customers',
                  icon: ContactRound,
              },
              ...(props.canManageTeam === true || path.startsWith(base + '/team')
                  ? [
                        {
                            title: bn ? 'টিম' : 'Team',
                            href: base + '/team',
                            icon: UsersRound,
                        },
                    ]
                  : []),
              ...(props.canConfigure === true || path.startsWith(base + '/configuration')
                  ? [
                        {
                            title: bn ? 'সেটিংস' : 'Configuration',
                            href: base + '/configuration',
                            icon: Settings2,
                        },
                    ]
                  : []),
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {workspace && (
                    <SidebarGroup className="px-2 pt-4">
                        <SidebarGroupLabel className="truncate" title={workspace.name}>
                            {workspace.name}
                        </SidebarGroupLabel>
                        <SidebarMenu>
                            {workspaceItems.map((item) => (
                                <SidebarMenuItem key={item.href}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={
                                            path === item.href ||
                                            (item.href !== base &&
                                                path.startsWith(item.href + '/'))
                                        }
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            <item.icon aria-hidden="true" />
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
