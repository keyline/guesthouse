# Guesthouse Management System - Features Overview

## ✅ System Capabilities

### 1. Multi-Property Management
- Manage multiple properties across different locations
- Each property has:
  - Name, Type (Guest House/Banquet/Mixed)
  - Location (City, State, Country, Postal Code, Address)
  - Contact Info (Phone, Email, Manager Name)
  - Check-in/Check-out Times
  - Base Pricing
  - Short Description & Full Description
  - Policies (JSON format)
  - Status (Draft/Active/Inactive)
  - Publication Date

### 2. Property Gallery
- Multiple images per property
- Set primary image
- Sort order for gallery display
- Alt text for SEO

### 3. Property Amenities
- Attach amenities (features) to properties
- Examples: WiFi, Pool, AC, Parking, etc.
- Shared amenity list across all properties

### 4. Room Type Management (Per Property)
- Multiple room types per property
- Each room type includes:
  - Name & Code
  - Max Adults & Max Children
  - Base Occupancy
  - **Base Price** (Currency in INR)
  - Description
  - Status (Active/Inactive)
  - **Room Type Images** (Multiple photos)
  - **Room Type Amenities** (Features specific to this room type)

### 5. Room Type Images
- Multiple photos per room type
- Primary image selector
- Sort order
- Alt text

### 6. Room Type Amenities/Features
- Specific amenities for each room type
- Examples:
  - Double Bed
  - TV
  - Balcony
  - Sea View
  - Kitchen
  - Jacuzzi
  - etc.

### 7. Individual Rooms
- Multiple rooms per property
- Each room linked to a room type
- Room Number & Floor
- Status (Available/Maintenance/Blocked)
- Smoking/Non-Smoking option
- Accessibility option
- Notes

### 8. Bookings Management
- Track guest bookings
- Check-in/Check-out dates
- Guest information
- Room assignment
- Payment status

---

## 📊 Database Structure

```
Properties (Multiple per system)
├── Room Types (Multiple per property)
│   ├── Room Type Images (Multiple photos per type)
│   └── Room Type Amenities (Features/Facilities)
├── Rooms (Physical rooms, linked to room types)
├── Property Images (Property gallery)
├── Amenities (Applied to property)
└── Bookings
```

---

## 🎯 Super Admin Workflow

### Step 1: Create a New Property
1. Go to: `/admin/properties`
2. Click "Create New Property"
3. Fill in:
   - Property Name
   - Location (City, State, Country, Address)
   - Contact Details
   - Pricing & Currency
   - Description
4. Upload Property Images
5. Select Property Amenities
6. Save

### Step 2: Create Room Types for Property
1. Go to: `/admin/room-types`
2. Click "Create New Room Type"
3. Fill in:
   - Room Type Name (e.g., "Deluxe Room", "Suite", etc.)
   - Occupancy (Max Adults, Max Children)
   - Base Price
   - Description
4. **Upload Room Type Images** (Multiple photos)
5. **Select Room Amenities** (Features like WiFi, AC, TV, etc.)
6. Save

### Step 3: Add Individual Rooms
1. Go to: `/admin/rooms`
2. Click "Create New Room"
3. Fill in:
   - Room Number
   - Floor Level
   - Select Room Type
   - Smoking/Non-Smoking preference
   - Accessibility option
   - Notes
4. Save

### Step 4: Manage Amenities
1. Go to: Amenities section
2. Create standard amenities (used across properties/room types)
3. Examples:
   - WiFi
   - Air Conditioning
   - TV
   - Balcony
   - Sea View
   - Swimming Pool
   - Parking
   - Kitchen
   - Jacuzzi

---

## 💰 Pricing Structure

- **Property Level**: Base price (default for all rooms)
- **Room Type Level**: Override property price with room-type specific price
- **Currency**: All prices in INR (configurable per property)
- **Price Format**: Stored in minor units (paise) - divide by 100 for display

Example:
- Room Type Price: 5000 (minor units) = ₹50.00

---

## 🖼️ Image Management

### Property Images
- Unlimited images per property
- Primary image (used in listings)
- Alt text for accessibility

### Room Type Images
- Unlimited images per room type
- Primary image (gallery thumbnail)
- Sort order
- Alt text

---

## 🏠 Property Types
- **Guest House**: Residential accommodation
- **Banquet**: Event/Function spaces
- **Mixed**: Both services

---

## 📱 Current Features in Admin Panel

✅ Property Management (Create, Edit, Delete)
✅ Room Type Management with images
✅ Individual Room Management
✅ Booking Management
✅ Guest Management
✅ Amenity Management
✅ Availability Calendar
✅ Dashboard Analytics

---

## 🔐 Access Control

- **Super Admin**: Full access to all properties
- **Property Manager**: Access to assigned properties only

---

## 📝 Next Steps

1. Log in as Super Admin
2. Create your first Property
3. Add Room Types with photos and amenities
4. Create Individual Rooms
5. Start accepting bookings!

---

## 🆘 Support URLs

- Admin Dashboard: http://localhost:8080/admin/dashboard
- Properties: http://localhost:8080/admin/properties
- Room Types: http://localhost:8080/admin/room-types
- Rooms: http://localhost:8080/admin/rooms
- Bookings: http://localhost:8080/admin/bookings
- Guests: http://localhost:8080/admin/guests
- Amenities: (Added to system)

---

**Admin Credentials:**
- Email: admin@example.com
- Password: Password#123

Created: 2026-07-07
