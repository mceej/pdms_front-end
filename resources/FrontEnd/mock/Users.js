export function seedUsers() {
    return [{
            id: 'admin-001',
            name: 'System Administrator',
            email: 'admin@dswd.gov.ph',
            password: 'Admin@123',
            section: 'CIS',
            role: 'ADMIN',
            status: 'Active',
        },
        { id: 'user-101', name: 'Mikaella Summer', email: 'msgorgonio@gmail.com', password: 'password123', section: 'CIS', role: 'MANCOM', status: 'Active' },
        { id: 'user-102', name: 'Oliver Orano', email: 'oorano7@gmail.com', password: 'password123', section: 'DRMD', role: 'RDC Focal', status: 'Active' },
        { id: 'user-103', name: 'Michael John', email: 'mjquisaot@gmail.com', password: 'password123', section: 'CIS', role: 'MANCOM', status: 'Inactive' },
        { id: 'user-104', name: 'Benedict Solo', email: 'soloben@gmail.com', password: 'password123', section: 'CIS', role: 'ADMIN', status: 'Active' },
        { id: 'user-105', name: 'John Carlo', email: 'hellomorre@gmail.com', password: 'password123', section: 'DRMD', role: 'RDC Focal', status: 'Deactivated' },
        { id: 'user-106', name: 'Raymund Raj', email: 'rrgo@gmail.com', password: 'password123', section: 'CIS', role: 'MANCOM', status: 'Active' },
    ];
}

export function createUserId() {
    return `user-${Date.now()}-${Math.round(Math.random() * 1000)}`;
}

export const SECTION_OPTIONS = ['CIS', 'DRMD'];
export const ROLE_OPTIONS = ['MANCOM', 'RDC Focal', 'ADMIN'];
export const STATUS_OPTIONS = ['Active', 'Inactive', 'Deactivated'];