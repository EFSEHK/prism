import Svg, { Circle, Path, Rect, Line } from 'react-native-svg'

function IconShell({ size, color, children }) {
  return (
    <Svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke={color}
      strokeWidth={1.75}
      strokeLinecap="round"
      strokeLinejoin="round"
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
    >
      {children}
    </Svg>
  )
}

export function HomeworkIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
      <Path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
      <Line x1="8" y1="7" x2="16" y2="7" />
      <Line x1="8" y1="11" x2="14" y2="11" />
    </IconShell>
  )
}

export function MarksIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
      <Path d="M14 2v6h6" />
      <Path d="M9 15l2 2 4-4" />
    </IconShell>
  )
}

export function AttendanceIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Rect x="3" y="4" width="18" height="18" rx="2" />
      <Line x1="16" y1="2" x2="16" y2="6" />
      <Line x1="8" y1="2" x2="8" y2="6" />
      <Line x1="3" y1="10" x2="21" y2="10" />
      <Path d="M9 16l2 2 4-4" />
    </IconShell>
  )
}

export function TimetableIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Circle cx="12" cy="12" r="9" />
      <Path d="M12 7v5l3.5 2" />
    </IconShell>
  )
}

export function NotificationsIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
      <Path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </IconShell>
  )
}

export function FeesIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Rect x="2" y="5" width="20" height="14" rx="2" />
      <Line x1="2" y1="10" x2="22" y2="10" />
      <Circle cx="17" cy="15" r="1.25" fill={color} stroke="none" />
    </IconShell>
  )
}

export function OnlineIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M23 7l-7 5 7 5V7z" />
      <Rect x="1" y="5" width="15" height="14" rx="2" />
    </IconShell>
  )
}

export function LeaveIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <Circle cx="9" cy="7" r="4" />
      <Line x1="17" y1="11" x2="23" y2="11" />
      <Line x1="20" y1="8" x2="20" y2="14" />
    </IconShell>
  )
}

export function ApprovalsIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M9 11l3 3L22 4" />
      <Path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
    </IconShell>
  )
}

export function UsersIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
      <Circle cx="9" cy="7" r="4" />
      <Path d="M23 21v-2a4 4 0 0 0-3-3.87" />
      <Path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </IconShell>
  )
}

export function ConfigIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Circle cx="12" cy="12" r="3" />
      <Path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
    </IconShell>
  )
}

export function PermissionsIcon({ size = 28, color = '#2563eb' }) {
  return (
    <IconShell size={size} color={color}>
      <Rect x="3" y="11" width="18" height="11" rx="2" />
      <Path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </IconShell>
  )
}

export const FEATURE_ICON_MAP = {
  homework: HomeworkIcon,
  marks: MarksIcon,
  attendance: AttendanceIcon,
  timetable: TimetableIcon,
  notifications: NotificationsIcon,
  fees: FeesIcon,
  online: OnlineIcon,
  leave: LeaveIcon,
  approvals: ApprovalsIcon,
  users: UsersIcon,
  configuration: ConfigIcon,
  permissions: PermissionsIcon,
}
